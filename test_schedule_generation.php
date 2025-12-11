<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Tambahkan ini untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fungsi yang sama seperti di process_ai.php
function get_available_days($available_days_count) {
    switch ($available_days_count) {
        case 1:
            return ['Senin'];
        case 2:
            return ['Senin', 'Kamis'];
        case 3:
            return ['Senin', 'Rabu', 'Jumat'];
        case 4:
            return ['Senin', 'Selasa', 'Kamis', 'Jumat'];
        case 5:
            return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        case 6:
            return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        case 7:
            return ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        default:
            return ['Senin', 'Rabu', 'Jumat'];
    }
}

// Fungsi untuk mengirim permintaan ke OpenAI API
function generate_workout_schedule_openai($target_muscles, $training_goals, $experience_level, $available_days_count, $time_per_session) {
    global $OPENAI_API_KEY, $OPENAI_MODEL;
    
    $api_url = 'https://api.openai.com/v1/chat/completions';
    
    // Ambil hari yang tersedia untuk latihan
    $day_names = get_available_days($available_days_count);
    
    $prompt = "Buatkan jadwal latihan otomatis dalam format JSON berdasarkan informasi berikut:\n\n" .
              "Target otot: {$target_muscles}\n" .
              "Tujuan latihan: {$training_goals}\n" .
              "Tingkat pengalaman: {$experience_level}\n" .
              "Hari latihan: " . implode(', ', $day_names) . "\n" .
              "Durasi per sesi: {$time_per_session} menit\n\n" .

              "Format JSON yang diharapkan:\n" .
              "{\n" .
              "  \"schedule\": [\n" .
              "    {\n" .
              "      \"day_name\": \"Nama Hari\",\n" .
              "      \"exercises\": [\n" .
              "        {\n" .
              "          \"name\": \"Nama Latihan\",\n" .
              "          \"sets\": jumlah_set,\n" .
              "          \"reps\": jumlah_reps,\n" .
              "          \"rest\": waktu_istirahat_dalam_detik,\n" .
              "          \"notes\": \"Catatan tambahan jika diperlukan\"\n" .
              "        }\n" .
              "      ]\n" .
              "    }\n" .
              "  ]\n" .
              "}\n\n" .

              "Gunakan maksimal 5 latihan per hari untuk menjaga durasi sesi sesuai permintaan. " .
              "Pastikan jumlah set, reps, dan waktu istirahat sesuai dengan tingkat pengalaman (pemula perlu istirahat lebih lama dan set yang lebih sedikit). " .
              "Sertakan variasi latihan untuk mencegah kebosanan. " .
              "PENTING: Berikan jawaban langsung berupa JSON tanpa penjelasan tambahan atau format markdown lainnya.";

    $data = [
        'model' => $OPENAI_MODEL,
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 2000
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("Curl error: {$error}");
    }

    if ($http_code !== 200) {
        throw new Exception("API request failed with HTTP code {$http_code}");
    }

    $result = json_decode($response, true);
    
    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        throw new Exception("Invalid API response format");
    }

    // Ambil konten JSON dari respons
    $content = $result['choices'][0]['message']['content'];
    
    // Bersihkan konten jika ada teks tambahan di sekitar JSON
    $json_start = strpos($content, '{');
    $json_end = strrpos($content, '}');
    if ($json_start !== false && $json_end !== false) {
        $json_content = substr($content, $json_start, $json_end - $json_start + 1);
        $decoded = json_decode($json_content, true);
        
        if ($decoded) {
            return $decoded;
        }
    }
    
    // Jika tidak bisa parsing JSON dari respons, coba lagi dengan regex
    preg_match('/\{.*\}/s', $content, $matches);
    if (isset($matches[0])) {
        $decoded = json_decode($matches[0], true);
        if ($decoded) {
            return $decoded;
        }
    }
    
    throw new Exception("Could not extract valid JSON from API response: " . $content);
}

// Fungsi untuk mengirim permintaan ke Gemini API
function generate_workout_schedule_gemini($target_muscles, $training_goals, $experience_level, $available_days_count, $time_per_session) {
    global $GEMINI_API_KEY, $GEMINI_MODEL;
    
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$GEMINI_MODEL}:generateContent?key={$GEMINI_API_KEY}";
    
    // Ambil hari yang tersedia untuk latihan
    $day_names = get_available_days($available_days_count);
    
    $prompt = "Buatkan jadwal latihan otomatis dalam format JSON berdasarkan informasi berikut:\n\n" .
              "Target otot: {$target_muscles}\n" .
              "Tujuan latihan: {$training_goals}\n" .
              "Tingkat pengalaman: {$experience_level}\n" .
              "Hari latihan: " . implode(', ', $day_names) . "\n" .
              "Durasi per sesi: {$time_per_session} menit\n\n" .

              "Format JSON yang diharapkan:\n" .
              "{\n" .
              "  \"schedule\": [\n" .
              "    {\n" .
              "      \"day_name\": \"Nama Hari\",\n" .
              "      \"exercises\": [\n" .
              "        {\n" .
              "          \"name\": \"Nama Latihan\",\n" .
              "          \"sets\": jumlah_set,\n" .
              "          \"reps\": jumlah_reps,\n" .
              "          \"rest\": waktu_istirahat_dalam_detik,\n" .
              "          \"notes\": \"Catatan tambahan jika diperlukan\"\n" .
              "        }\n" .
              "      ]\n" .
              "    }\n" .
              "  ]\n" .
              "}\n\n" .

              "Gunakan maksimal 5 latihan per hari untuk menjaga durasi sesi sesuai permintaan. " .
              "Pastikan jumlah set, reps, dan waktu istirahat sesuai dengan tingkat pengalaman (pemula perlu istirahat lebih lama dan set yang lebih sedikit). " .
              "Sertakan variasi latihan untuk mencegah kebosanan. " .
              "PENTING: Berikan jawaban langsung berupa JSON tanpa penjelasan tambahan atau format markdown lainnya.";

    $data = [
        'contents' => [
            'parts' => [
                [
                    'text' => $prompt
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 4000
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("Curl error: {$error}");
    }

    if ($http_code !== 200) {
        throw new Exception("API request failed with HTTP code {$http_code}");
    }

    $result = json_decode($response, true);
    
    if (!$result || !isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception("Invalid API response format: " . print_r($result, true));
    }

    // Ambil konten JSON dari respons
    $content = $result['candidates'][0]['content']['parts'][0]['text'];

    // Bersihkan respons dari markdown code block
    $content = trim($content);
    if (strpos($content, '```json') === 0) {
        $content = substr($content, 7); // Hilangkan '```json'
    } elseif (strpos($content, '```') === 0) {
        $content = substr($content, 3); // Hilangkan '```'
    }

    if (strrpos($content, '```') === strlen($content) - 3) {
        $content = substr($content, 0, strlen($content) - 3); // Hilangkan '```' di akhir
    }

    $content = trim($content);

    // Coba parsing JSON
    $decoded = json_decode($content, true);
    if ($decoded) {
        return $decoded;
    }

    // Jika parsing langsung gagal, coba cari blok JSON di dalam teks
    $json_start = strpos($content, '{');
    $json_end = strrpos($content, '}');
    if ($json_start !== false && $json_end !== false) {
        $json_content = substr($content, $json_start, $json_end - $json_start + 1);
        $decoded = json_decode($json_content, true);

        if ($decoded) {
            return $decoded;
        }
    }

    // Jika tidak bisa parsing JSON dari respons, coba lagi dengan regex
    preg_match('/\{.*\}/s', $content, $matches);
    if (isset($matches[0])) {
        $decoded = json_decode($matches[0], true);
        if ($decoded) {
            return $decoded;
        }
    }

    throw new Exception("Could not extract valid JSON from API response: " . $content);
}

// Fungsi utama untuk menghasilkan jadwal latihan berdasarkan provider yang dipilih
function generate_workout_schedule($target_muscles, $training_goals, $experience_level, $available_days_count, $time_per_session) {
    global $AI_PROVIDER, $OPENAI_API_KEY, $GEMINI_API_KEY;
    
    if ($AI_PROVIDER === 'gemini') {
        if (empty($GEMINI_API_KEY)) {
            throw new Exception('API key Gemini belum diset');
        }
        return generate_workout_schedule_gemini($target_muscles, $training_goals, $experience_level, $available_days_count, $time_per_session);
    } else { // Default to OpenAI
        if (empty($OPENAI_API_KEY)) {
            throw new Exception('API key OpenAI belum diset');
        }
        return generate_workout_schedule_openai($target_muscles, $training_goals, $experience_level, $available_days_count, $time_per_session);
    }
}

// Test dengan parameter contoh
echo "Testing AI generation...\n";

try {
    $test_schedule = generate_workout_schedule(
        "Dada, Bahu, Trisep",
        "Membentuk otot dan meningkatkan kekuatan",
        "beginner",
        3,
        45
    );
    
    echo "Success! Generated schedule:\n";
    echo json_encode($test_schedule, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}