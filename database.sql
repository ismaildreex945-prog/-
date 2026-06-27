CREATE DATABASE IF NOT EXISTS ribat_smart_assistant
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE ribat_smart_assistant;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    user_type ENUM('student','doctor','registrar') NOT NULL,
    password VARCHAR(255) NOT NULL,
    university_id VARCHAR(20) NULL,
    academic_level VARCHAR(50) NULL,
    specialization VARCHAR(100) NULL,
    degree VARCHAR(100) NULL,
    experience_years INT NULL DEFAULT 0,
    exact_specialization VARCHAR(100) NULL,
    job_number VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_number VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    receipt_image LONGTEXT NULL,
    status ENUM('pending','confirmed','rejected') DEFAULT 'pending',
    payment_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    coursework INT DEFAULT 0,
    exam INT DEFAULT 0,
    total INT GENERATED ALWAYS AS (coursework + exam) STORED,
    grade VARCHAR(10) GENERATED ALWAYS AS (
        CASE
            WHEN (coursework + exam) >= 90 THEN 'A'
            WHEN (coursework + exam) >= 85 THEN 'B+'
            WHEN (coursework + exam) >= 80 THEN 'B'
            WHEN (coursework + exam) >= 75 THEN 'C+'
            WHEN (coursework + exam) >= 70 THEN 'C'
            WHEN (coursework + exam) >= 50 THEN 'D'
            ELSE 'F'
        END
    ) STORED,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;