<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit;
}

$user = getUserById($_SESSION['user_id']);
if (!$user || $user['user_type'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بإضافة محاضرات']);
    exit;
}

$day = trim($_POST['lectureDay'] ?? '');
$subject = trim($_POST['lectureSubject'] ?? '');
$timeSlot = trim($_POST['lectureTime'] ?? '');
$classroom = trim($_POST['lectureRoom'] ?? '');

if ($day === '' || $subject === '' || $timeSlot === '' || $classroom === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'جميع حقول المحاضرة مطلوبة']);
    exit;
}

$success = addLecture($user['id'], $day, $subject, $timeSlot, $classroom);
if ($success) {
    echo json_encode(['success' => true, 'message' => 'تمت إضافة المحاضرة بنجاح']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'فشل حفظ المحاضرة']);
}
