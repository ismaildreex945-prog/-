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

$userId = (int) $_SESSION['user_id'];
$uniId = trim($_POST['uniId'] ?? '');
$txnNum = trim($_POST['txnNum'] ?? '');
$amount = trim($_POST['amount'] ?? '');

if ($uniId === '' || $txnNum === '' || $amount === '' || !is_numeric($amount) || (float) $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'بيانات الدفع غير كاملة']);
    exit;
}

$receiptImage = null;
if (!empty($_FILES['receiptFile']['name'])) {
    if ($_FILES['receiptFile']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'فشل رفع الإيصال']);
        exit;
    }

    $ext = pathinfo($_FILES['receiptFile']['name'], PATHINFO_EXTENSION);
    $safeExt = strtolower($ext);
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    if (!in_array($safeExt, $allowed, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'صيغة الإيصال غير مسموح بها']);
        exit;
    }

    $fileName = 'receipt_' . $userId . '_' . time() . '.' . $safeExt;
    $targetPath = __DIR__ . '/uploads/receipts/' . $fileName;
    if (!move_uploaded_file($_FILES['receiptFile']['tmp_name'], $targetPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'تعذر حفظ الإيصال']);
        exit;
    }

    $receiptImage = 'uploads/receipts/' . $fileName;
}

$success = addPayment($userId, $txnNum, (float) $amount, $receiptImage, date('Y-m-d'));

if ($success) {
    echo json_encode(['success' => true, 'message' => 'تم إرسال الدفع للمسجل بنجاح']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'فشل حفظ الدفع']);
}
