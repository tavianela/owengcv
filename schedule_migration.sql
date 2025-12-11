-- Membuat tabel untuk jadwal latihan
CREATE TABLE IF NOT EXISTS workout_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    schedule_name VARCHAR(255) NOT NULL,
    schedule_type ENUM('manual', 'ai_generated') NOT NULL DEFAULT 'manual',
    target_muscle_groups TEXT,
    training_goals TEXT,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    schedule_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Membuat tabel untuk detail jadwal latihan harian
CREATE TABLE IF NOT EXISTS schedule_days (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    day_of_week INT NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
    day_name VARCHAR(50) NOT NULL,
    exercises JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES workout_schedules(id) ON DELETE CASCADE
);

-- Menambahkan indeks untuk pencarian yang lebih cepat
CREATE INDEX idx_member_schedule ON workout_schedules(member_id);
CREATE INDEX idx_schedule_type ON workout_schedules(schedule_type);