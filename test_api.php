<?php
require_once __DIR__ . '/config.php';

// Tambahkan ini untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

function test_gemini_api() {
    global $GEMINI_API_KEY, $GEMINI_MODEL;
    
    if (empty($GEMINI_API_KEY)) {
        echo "GEMINI_API_KEY belum diset\n";
        return false;
    }
    
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-latest:generateContent?key={$GEMINI_API_KEY}";
    
    $prompt = "Hanya jawab dengan 'OK' jika Anda bisa mengakses API ini. Jangan memberikan penjelasan tambahan atau pikiran internal, hanya jawab 'OK'.";
    
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
            'maxOutputTokens' => 1000
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
        echo "Curl error: {$error}\n";
        return false;
    }

    if ($http_code !== 200) {
        echo "HTTP error: {$http_code}\n";
        echo "Response: {$response}\n";
        return false;
    }

    $result = json_decode($response, true);
    
    if (!$result || !isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        echo "Invalid response format\n";
        echo "Response: {$response}\n";
        return false;
    }

    echo "API test successful: " . $result['candidates'][0]['content']['parts'][0]['text'] . "\n";
    return true;
}

echo "Testing Gemini API...\n";
test_gemini_api();