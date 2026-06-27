<?php
$host = 'localhost';
$dbname = 'ribat_smart_assistant';
$username = 'root';
$password = '';

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void {
    $safeTable = str_replace('`', '', $table);
    $safeColumn = str_replace('`', '', $column);

    $stmt = $pdo->query("SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    if ($stmt->fetch()) {
        return;
    }

    $pdo->exec("ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
}

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, $pdoOptions);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $pdoOptions);

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
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
    ) ENGINE=InnoDB");

    ensureColumn($pdo, 'users', 'university_id', 'VARCHAR(20) NULL');
    ensureColumn($pdo, 'users', 'academic_level', 'VARCHAR(50) NULL');
    ensureColumn($pdo, 'users', 'specialization', 'VARCHAR(100) NULL');
    ensureColumn($pdo, 'users', 'degree', 'VARCHAR(100) NULL');
    ensureColumn($pdo, 'users', 'experience_years', 'INT NULL DEFAULT 0');
    ensureColumn($pdo, 'users', 'exact_specialization', 'VARCHAR(100) NULL');
    ensureColumn($pdo, 'users', 'job_number', 'VARCHAR(50) NULL');
    ensureColumn($pdo, 'users', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        transaction_number VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        receipt_image LONGTEXT NULL,
        status ENUM('pending','confirmed','rejected') DEFAULT 'pending',
        payment_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    ensureColumn($pdo, 'payments', 'transaction_number', 'VARCHAR(50) NOT NULL');
    ensureColumn($pdo, 'payments', 'amount', 'DECIMAL(10,2) NOT NULL');
    ensureColumn($pdo, 'payments', 'receipt_image', 'LONGTEXT NULL');
    ensureColumn($pdo, 'payments', 'status', "ENUM('pending','confirmed','rejected') DEFAULT 'pending'");
    ensureColumn($pdo, 'payments', 'payment_date', 'DATE NOT NULL');
    ensureColumn($pdo, 'payments', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

    $pdo->exec("CREATE TABLE IF NOT EXISTS lectures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        day VARCHAR(50) NOT NULL,
        subject VARCHAR(100) NOT NULL,
        time_slot VARCHAR(50) NOT NULL,
        classroom VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    ensureColumn($pdo, 'lectures', 'doctor_id', 'INT NOT NULL');
    ensureColumn($pdo, 'lectures', 'day', 'VARCHAR(50) NOT NULL');
    ensureColumn($pdo, 'lectures', 'subject', 'VARCHAR(100) NOT NULL');
    ensureColumn($pdo, 'lectures', 'time_slot', 'VARCHAR(50) NOT NULL');
    ensureColumn($pdo, 'lectures', 'classroom', 'VARCHAR(50) NOT NULL');
    ensureColumn($pdo, 'lectures', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

    $pdo->exec("CREATE TABLE IF NOT EXISTS grades (
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
    ) ENGINE=InnoDB");
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}
?>