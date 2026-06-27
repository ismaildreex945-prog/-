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
if (!$user || $user['user_type'] !== 'registrar') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية لهذا الإجراء']);
    exit;
}

$paymentId = isset($_POST['payment_id']) ? (int) $_POST['payment_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if ($paymentId <= 0 || !in_array($status, ['confirmed', 'rejected'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'بيانات غير صحيحة']);
    exit;
}

if (updatePaymentStatus($paymentId, $status)) {
    echo json_encode(['success' => true, 'message' => 'تم تحديث الحالة بنجاح']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'فشل تحديث الحالة']);
}
