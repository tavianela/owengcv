<?php
require_once __DIR__ . '/config.php';

function list_available_models() {
    global $GEMINI_API_KEY;
    
    if (empty($GEMINI_API_KEY)) {
        echo "GEMINI_API_KEY belum diset\n";
        return false;
    }
    
    $api_url = "https://generativelanguage.googleapis.com/v1beta/models?key={$GEMINI_API_KEY}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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
    
    if (!$result || !isset($result['models'])) {
        echo "Invalid response format\n";
        echo "Response: {$response}\n";
        return false;
    }

    echo "Available models:\n";
    foreach ($result['models'] as $model) {
        echo "- {$model['name']}\n";
        if (isset($model['supportedGenerationMethods'])) {
            echo "  Supported methods: " . implode(', ', $model['supportedGenerationMethods']) . "\n";
        }
        echo "\n";
    }
    
    return true;
}

echo "Listing available models...\n";
list_available_models();