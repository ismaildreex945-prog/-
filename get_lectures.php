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
if (!$user) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$lectures = getLectures($user['user_type'] === 'doctor' ? $user['id'] : null);
echo json_encode(['success' => true, 'lectures' => $lectures]);
