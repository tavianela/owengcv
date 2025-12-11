<?php
require_once __DIR__ . '/db.php';

$pdo = get_pdo();

try {
    $stmt = $pdo->query('DESCRIBE users;');
    $result = $stmt->fetchAll();
    
    echo "Struktur tabel users:\n";
    foreach ($result as $row) {
        echo "- {$row['Field']} ({$row['Type']}) - {$row['Key']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Coba cek apakah tabel users ada
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'users';");
        $result = $stmt->fetchAll();
        if (empty($result)) {
            echo "Tabel 'users' tidak ditemukan di database.\n";
        } else {
            echo "Tabel 'users' ditemukan.\n";
        }
    } catch (Exception $e2) {
        echo "Error saat memeriksa tabel: " . $e2->getMessage() . "\n";
    }
}