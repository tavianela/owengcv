<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function run_migration() {
    $pdo = get_pdo();
    
    try {
        // Periksa apakah tabel workout_schedules sudah ada
        $stmt = $pdo->query("SHOW TABLES LIKE 'workout_schedules';");
        $table_exists = $stmt->rowCount() > 0;
        
        if (!$table_exists) {
            // Membuat tabel workout_schedules
            $sql = "
            CREATE TABLE workout_schedules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                member_id INT NOT NULL,
                schedule_name VARCHAR(255) NOT NULL,
                schedule_type ENUM('manual', 'ai_generated') NOT NULL DEFAULT 'manual',
                target_muscle_groups TEXT,
                training_goals TEXT,
                difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
                schedule_data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            );
            ";
            $pdo->exec($sql);
            echo "Tabel workout_schedules berhasil dibuat.\n";
        } else {
            echo "Tabel workout_schedules sudah ada.\n";
        }
        
        // Periksa apakah tabel schedule_days sudah ada
        $stmt = $pdo->query("SHOW TABLES LIKE 'schedule_days';");
        $table_exists = $stmt->rowCount() > 0;
        
        if (!$table_exists) {
            // Membuat tabel schedule_days
            $sql = "
            CREATE TABLE schedule_days (
                id INT AUTO_INCREMENT PRIMARY KEY,
                schedule_id INT NOT NULL,
                day_of_week INT NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
                day_name VARCHAR(50) NOT NULL,
                exercises JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            );
            ";
            $pdo->exec($sql);
            echo "Tabel schedule_days berhasil dibuat.\n";
        } else {
            echo "Tabel schedule_days sudah ada.\n";
        }
        
        // Tambahkan indeks jika belum ada
        try {
            $pdo->exec("CREATE INDEX idx_member_schedule ON workout_schedules(member_id);");
            echo "Indeks idx_member_schedule berhasil ditambahkan.\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                echo "Peringatan: Gagal menambahkan indeks idx_member_schedule: " . $e->getMessage() . "\n";
            } else {
                echo "Indeks idx_member_schedule sudah ada.\n";
            }
        }
        
        try {
            $pdo->exec("CREATE INDEX idx_schedule_type ON workout_schedules(schedule_type);");
            echo "Indeks idx_schedule_type berhasil ditambahkan.\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                echo "Peringatan: Gagal menambahkan indeks idx_schedule_type: " . $e->getMessage() . "\n";
            } else {
                echo "Indeks idx_schedule_type sudah ada.\n";
            }
        }
        
        try {
            $pdo->exec("CREATE INDEX idx_schedule_id ON schedule_days(schedule_id);");
            echo "Indeks idx_schedule_id berhasil ditambahkan.\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                echo "Peringatan: Gagal menambahkan indeks idx_schedule_id: " . $e->getMessage() . "\n";
            } else {
                echo "Indeks idx_schedule_id sudah ada.\n";
            }
        }
        
        echo "\nMigration selesai!\n";
        echo "Tabel workout_schedules dan schedule_days siap digunakan.\n";
        echo "Catatan: Foreign key constraints tidak ditambahkan untuk menghindari masalah kompatibilitas.\n";
        echo "Namun, aplikasi akan tetap berfungsi dengan validasi di sisi aplikasi.\n";
    } catch (PDOException $e) {
        echo "Error saat menjalankan migration: " . $e->getMessage() . "\n";
    }
}

// Jalankan migrasi jika file ini diakses langsung
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    run_migration();
}