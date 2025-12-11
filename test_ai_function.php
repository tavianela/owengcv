<?php
require_once __DIR__ . '/config.php';

// Kita akan menguji fungsi-fungsi yang sudah kita buat di ai.php
// Untuk kemudahan, kita hanya menguji fungsi generate_ai_response

// Fungsi untuk mengirim permintaan ke OpenAI API
function generate_response_openai(string $prompt): array {
    global $OPENAI_API_KEY, $OPENAI_MODEL;
    
    $payload = [
        'model' => $OPENAI_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => 'You are an expert gym coach for FozGym. Keep answers practical, concise, and actionable with bullet points when helpful. Language: Indonesian.'],
            ['role' => 'user', 'content' => $prompt],
        ],
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $OPENAI_API_KEY,
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $out = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Curl error: ' . $error];
    }
    
    $json = json_decode($out, true);
    
    if (isset($json['choices'][0]['message']['content'])) {
        return ['success' => true, 'response' => $json['choices'][0]['message']['content']];
    } else {
        return ['success' => false, 'error' => 'Invalid API response format', 'raw_response' => $out];
    }
}

// Fungsi untuk mengirim permintaan ke Gemini API
function generate_response_gemini(string $prompt): array {
    global $GEMINI_API_KEY, $GEMINI_MODEL;
    
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$GEMINI_MODEL}:generateContent?key={$GEMINI_API_KEY}";

    // Gabungkan sistem instruksi ke dalam prompt karena beberapa model Gemini tidak mendukung systemInstruction
    $system_prompt = "Anda adalah seorang pelatih gym ahli untuk FozGym. Berikan jawaban yang praktis, ringkas, dan dapat ditindaklanjuti dengan bullet points jika perlu. Bahasa: Indonesia. Jawab secara langsung tanpa pikiran internal atau format markdown, hanya berikan teks biasa. ";
    $full_prompt = $system_prompt . $prompt;

    $data = [
        'contents' => [
            'parts' => [
                [
                    'text' => $full_prompt
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 2000
        ]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => 'Curl error: ' . $error];
    }

    if ($http_code !== 200) {
        return ['success' => false, 'error' => "API request failed with HTTP code {$http_code}. Raw response: " . $response];
    }

    $result = json_decode($response, true);

    if (!$result) {
        return ['success' => false, 'error' => 'Invalid API response format', 'raw_response' => $response];
    }

    // Periksa apakah ada kandidat
    if (!isset($result['candidates']) || !is_array($result['candidates']) || count($result['candidates']) === 0) {
        return ['success' => false, 'error' => 'No candidates in response', 'raw_response' => $response];
    }

    // Ambil kandidat pertama
    $candidate = $result['candidates'][0];

    // Periksa apakah kandidat memiliki finishReason MAX_TOKENS
    if (isset($candidate['finishReason']) && $candidate['finishReason'] === 'MAX_TOKENS') {
        // Khusus untuk Gemini, jika finishReason MAX_TOKENS tapi tidak ada teks,
        // kemungkinan besar karena model menggunakan token untuk reasoning/pikiran internal
        // Kita coba untuk mengecek apakah ada history interaksi di field lain
        if (isset($candidate['content']['parts']) && is_array($candidate['content']['parts']) && count($candidate['content']['parts']) > 0) {
            $part = $candidate['content']['parts'][0];
            if (isset($part['text'])) {
                return ['success' => true, 'response' => $part['text']];
            }
        }
        // Jika tetap tidak ada teks, coba cek apakah ada field lain yang berisi output
        // Tapi karena model Gemini terbaru menyimpan reasoning internal, kita harus menyesuaikan harapan
        return ['success' => false, 'error' => 'Response may have been consumed by internal reasoning. Try shorter prompts.', 'raw_response' => $response];
    }

    // Jika finishReason bukan MAX_TOKENS, cek apakah ada teks
    if (!isset($candidate['content']['parts'][0]['text'])) {
        return ['success' => false, 'error' => 'No text response in candidate', 'raw_response' => $response];
    }

    return ['success' => true, 'response' => $candidate['content']['parts'][0]['text']];
}

// Fungsi utama untuk menghasilkan respons AI berdasarkan provider yang dipilih
function generate_ai_response(string $prompt): array {
    global $AI_PROVIDER, $GEMINI_API_KEY, $OPENAI_API_KEY;

    if ($AI_PROVIDER === 'gemini') {
        if (empty($GEMINI_API_KEY)) {
            return ['success' => false, 'error' => 'Gemini API key not configured'];
        }
        return generate_response_gemini($prompt);
    } else { // Default to OpenAI
        if (empty($OPENAI_API_KEY)) {
            return ['success' => false, 'error' => 'OpenAI API key not configured'];
        }
        return generate_response_openai($prompt);
    }
}

echo "Testing AI Coach function with sample prompt...\n\n";

$test_prompt = "Saya pemula, ingin membentuk otot dada dan bahu. Berikan rekomendasi latihan 3x seminggu.";

$result = generate_ai_response($test_prompt);

if ($result['success']) {
    echo "SUCCESS! AI Response:\n";
    echo "----------------------\n";
    echo $result['response'] . "\n";
    echo "----------------------\n";
} else {
    echo "ERROR: " . $result['error'] . "\n";
    if (isset($result['raw_response'])) {
        echo "RAW RESPONSE: " . $result['raw_response'] . "\n";
    }
}

echo "\nTest completed.";