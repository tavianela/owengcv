<?php
require_once __DIR__ . '/config.php';

echo "=== Informasi Konfigurasi AI ===\n";
echo "AI Provider: {$AI_PROVIDER}\n";
echo "OpenAI API Key: " . (!empty($OPENAI_API_KEY) ? "Terkonfigurasi" : "Tidak ada") . "\n";
echo "Gemini API Key: " . (!empty($GEMINI_API_KEY) ? "Terkonfigurasi" : "Tidak ada") . "\n";
echo "OpenAI Model: {$OPENAI_MODEL}\n";
echo "Gemini Model: {$GEMINI_MODEL}\n";

echo "\n=== Status ===\n";
if ($AI_PROVIDER === 'gemini') {
    if (!empty($GEMINI_API_KEY)) {
        echo "Status: Gemini API siap digunakan\n";
    } else {
        echo "Status: Gemini API key belum diset\n";
    }
} else {
    if (!empty($OPENAI_API_KEY)) {
        echo "Status: OpenAI API siap digunakan\n";
    } else {
        echo "Status: OpenAI API key belum diset\n";
    }
}

echo "\n=== Catatan ===\n";
echo "File ai.php sekarang mendukung kedua layanan AI (OpenAI dan Gemini)\n";
echo "File schedules/process_ai.php juga mendukung kedua layanan AI\n";
echo "Model Gemini 2.0 Flash direkomendasikan untuk kompatibilitas terbaik\n";