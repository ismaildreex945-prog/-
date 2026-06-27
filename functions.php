<?php
require_once 'config.php';

function registerUser($fullName, $email, $phone, $userType, $password, $universityId = null, $academicLevel = null, $specialization = null, $degree = null, $experienceYears = null, $exactSpecialization = null, $jobNumber = null) {
    global $pdo;
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (full_name, email, phone, user_type, password, university_id, academic_level, specialization, degree, experience_years, exact_specialization, job_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$fullName, $email, $phone, $userType, $hashedPassword, $universityId, $academicLevel, $specialization, $degree, $experienceYears, $exactSpecialization, $jobNumber]);
}

function loginUser($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

function getUserById($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function getUserByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function updateUserProfile($userId, $data) {
    global $pdo;
    $fields = [];
    $values = [];
    foreach ($data as $field => $value) {
        $fields[] = "$field = ?";
        $values[] = $value;
    }
    $values[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($values);
}

function addPayment($userId, $transactionNumber, $amount, $receiptImage, $paymentDate) {
    global $pdo;
    $sql = "INSERT INTO payments (user_id, transaction_number, amount, receipt_image, payment_date) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$userId, $transactionNumber, $amount, $receiptImage, $paymentDate]);
}

function getPaymentsByUserId($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function addLecture($doctorId, $day, $subject, $timeSlot, $classroom) {
    global $pdo;
    $sql = "INSERT INTO lectures (doctor_id, day, subject, time_slot, classroom) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$doctorId, $day, $subject, $timeSlot, $classroom]);
}

function getLectures($doctorId = null) {
    global $pdo;
    if ($doctorId) {
        $stmt = $pdo->prepare("SELECT l.*, u.full_name AS doctor_name FROM lectures l JOIN users u ON l.doctor_id = u.id WHERE l.doctor_id = ? ORDER BY l.created_at DESC");
        $stmt->execute([$doctorId]);
    } else {
        $stmt = $pdo->query("SELECT l.*, u.full_name AS doctor_name FROM lectures l JOIN users u ON l.doctor_id = u.id ORDER BY l.created_at DESC");
    }
    return $stmt->fetchAll();
}

function getUserCountByType($userType) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM users WHERE user_type = ?");
    $stmt->execute([$userType]);
    $row = $stmt->fetch();
    return (int) ($row['total'] ?? 0);
}

function getPaymentsForRegistrar() {
    global $pdo;
    $stmt = $pdo->query("SELECT p.*, u.full_name, u.university_id FROM payments p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
    return $stmt->fetchAll();
}

function getPendingPayments() {
    global $pdo;
    $stmt = $pdo->query("SELECT p.*, u.full_name, u.university_id FROM payments p JOIN users u ON p.user_id = u.id WHERE p.status = 'pending'");
    return $stmt->fetchAll();
}

function updatePaymentStatus($paymentId, $status) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE payments SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $paymentId]);
}

function addGrade($studentId, $subject, $coursework, $exam) {
    global $pdo;
    $sql = "INSERT INTO grades (student_id, subject, coursework, exam) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$studentId, $subject, $coursework, $exam]);
}

function getStudentGrades($studentId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM grades WHERE student_id = ?");
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}
?>