<?php
require_once __DIR__ . '/config.php';
require_login();
include __DIR__ . '/partials/header.php';

function local_ai_fallback(string $prompt): string {
    $tips = [
        'Fokus pada teknik yang benar sebelum menambah beban.',
        'Kombinasikan latihan kekuatan dengan kardio untuk hasil optimal.',
        'Istirahat cukup: tidur 7–8 jam per hari sangat penting.',
        'Catat progres mingguan: beban, repetisi, dan durasi latihan.',
        'Jaga hidrasi dan nutrisi: protein 1.2–2g/kg berat badan.',
        'Pemanasan 5–10 menit dan pendinginan setelah latihan untuk mencegah cedera.',
    ];
    $goal = stripos($prompt, 'fat') !== false ? 'fokus fat loss' : (stripos($prompt, 'muscle') !== false || stripos($prompt, 'otot')!==false ? 'fokus pembentukan otot' : 'kebugaran umum');
    return "Rencana singkat ($goal)\n- " . $tips[array_rand($tips)] . "\n- Latihan 3–4x/minggu, progresif overload.\n- Nutrisi seimbang dan konsisten 8–12 minggu.";
}

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
    global $AI_PROVIDER, $OPENAI_API_KEY, $GEMINI_API_KEY;

    // Prefer sesuai konfigurasi, fallback ke provider lain jika key tidak tersedia
    if ($AI_PROVIDER === 'gemini') {
        if (!empty($GEMINI_API_KEY)) {
            return generate_response_gemini($prompt);
        }
        if (!empty($OPENAI_API_KEY)) {
            return generate_response_openai($prompt);
        }
        return ['success' => false, 'error' => 'Tidak ada API key yang terkonfigurasi'];
    } else { // default openai
        if (!empty($OPENAI_API_KEY)) {
            return generate_response_openai($prompt);
        }
        if (!empty($GEMINI_API_KEY)) {
            return generate_response_gemini($prompt);
        }
        return ['success' => false, 'error' => 'Tidak ada API key yang terkonfigurasi'];
    }
}

$response = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prompt = trim($_POST['prompt'] ?? '');
    if (!$prompt) {
        $error = 'Tulis pertanyaan atau tujuan latihan Anda.';
    } else {
        $ai_result = generate_ai_response($prompt);

        if ($ai_result['success']) {
            $response = $ai_result['response'];
        } else {
            $error = $ai_result['error'] . '. Menggunakan tips lokal.';
            $response = local_ai_fallback($prompt);
        }
    }
}
?>
<h2 class="mb-3"><img src="/FozGym/img/3.png" alt="AI Coach" class="section-icon"> AI Coach</h2>
<p>Tanyakan apapun terkait latihan, nutrisi, atau program FozGym. Sistem mendukung OpenAI atau Google Gemini API.</p>
<form method="post" class="row g-3" style="max-width:720px">
  <div class="col-12">
    <label class="form-label">Pertanyaan / Tujuan Latihan</label>
    <textarea name="prompt" class="form-control" rows="3" placeholder="Contoh: Saya ingin kurangi lemak dan bentuk otot, latihan 4x/minggu."></textarea>
  </div>
  <div class="col-12">
    <button class="btn btn-success">Tanya AI Coach</button>
    <?php if (!empty($OPENAI_API_KEY) || !empty($GEMINI_API_KEY)): ?>
        <?php
        // Tampilkan provider efektif berdasarkan konfigurasi + ketersediaan key
        $effective_provider = null;
        if ($AI_PROVIDER === 'gemini' && !empty($GEMINI_API_KEY)) {
            $effective_provider = 'gemini';
        } elseif ($AI_PROVIDER === 'openai' && !empty($OPENAI_API_KEY)) {
            $effective_provider = 'openai';
        } elseif (!empty($OPENAI_API_KEY)) {
            $effective_provider = 'openai';
        } elseif (!empty($GEMINI_API_KEY)) {
            $effective_provider = 'gemini';
        }
        ?>
        <span class="ms-2">AI Provider: <strong><?php echo $effective_provider === 'gemini' ? 'Google Gemini' : ($effective_provider === 'openai' ? 'OpenAI' : 'Tidak ada'); ?></strong></span>
<?php else: ?>
        <span class="text-warning ms-2">Peringatan: Tidak ada API key yang terkonfigurasi</span>
    <?php endif; ?>
  </div>
</form>
<?php if ($error): ?>
<div class="alert alert-warning mt-3"><?= htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($response): ?>
<div class="card mt-3">
    <div class="card-body">
        <h5 class="card-title">Saran AI Coach</h5>
        <pre style="white-space:pre-wrap" class="mb-0"><?= htmlspecialchars($response); ?></pre>
    </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/partials/footer.php'; ?>