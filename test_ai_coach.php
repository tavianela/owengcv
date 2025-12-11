<?php
require_once __DIR__ . '/config.php';

// Tambahkan ini untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing AI Coach API connection...\n\n";

// Test koneksi ke layanan AI yang dikonfigurasi
if ($AI_PROVIDER === 'gemini') {
    echo "Using Gemini API\n";
    echo "API Key configured: " . (!empty($GEMINI_API_KEY) ? "Yes" : "No") . "\n";
    echo "Model: {$GEMINI_MODEL} (current)\n";
    echo "Note: If experiencing issues, consider using gemini-2.0-flash for better compatibility\n\n";
} else {
    echo "Using OpenAI API\n";
    echo "API Key configured: " . (!empty($OPENAI_API_KEY) ? "Yes" : "No") . "\n";
    echo "Model: {$OPENAI_MODEL}\n\n";
}

// Lakukan tes permintaan sederhana
if (!empty($GEMINI_API_KEY) && $AI_PROVIDER === 'gemini') {
    echo "Testing Gemini API...\n";
    
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$GEMINI_MODEL}:generateContent?key={$GEMINI_API_KEY}";
    
    $system_prompt = "Anda adalah seorang pelatih gym ahli untuk FozGym. Berikan jawaban yang praktis, ringkas, dan dapat ditindaklanjuti dengan bullet points jika perlu. Bahasa: Indonesia. Jawab secara langsung tanpa pikiran internal atau format markdown, hanya berikan teks biasa. ";
    $full_prompt = $system_prompt . 'Hanya jawab dengan "OK" jika Anda bisa mengakses API ini.';

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
            'maxOutputTokens' => 200
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
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "Error: {$error}\n";
    } elseif ($http_code !== 200) {
        echo "HTTP Error: {$http_code}\n";
        echo "Response: {$response}\n";
    } else {
        $result = json_decode($response, true);
        if ($result && isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            echo "Success! Response: " . $result['candidates'][0]['content']['parts'][0]['text'] . "\n";
        } else {
            echo "Unexpected response format\n";
            echo "Response: {$response}\n";
        }
    }
} elseif (!empty($OPENAI_API_KEY) && $AI_PROVIDER !== 'gemini') {
    echo "Testing OpenAI API...\n";
    
    $payload = [
        'model' => $OPENAI_MODEL,
        'messages' => [
            ['role' => 'user', 'content' => 'Hanya jawab dengan "OK" jika Anda bisa mengakses API ini.'],
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
        echo "Error: {$error}\n";
    } else {
        $json = json_decode($out, true);
        if (isset($json['choices'][0]['message']['content'])) {
            echo "Success! Response: " . $json['choices'][0]['message']['content'] . "\n";
        } else {
            echo "Unexpected response format\n";
            echo "Response: {$out}\n";
        }
    }
} else {
    echo "No API key configured for the selected provider.\n";
    echo "Please set the appropriate API key in config.php\n";
}