<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = getUserById($_SESSION['user_id']);
if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$roleLabel = 'طالب';
if (!empty($user['academic_level'])) {
    $roleLabel .= ' - ' . $user['academic_level'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - طالب | المساعد الذكي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header"><div class="sidebar-logo"><i class="fas fa-robot"></i></div><div><strong>المساعد الذكي</strong><br><small>جامعة الرباط الوطني</small></div></div>
        <div class="sidebar-user"><div class="user-avatar student"><i class="fas fa-user-graduate"></i></div><div><strong><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br><small><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></small></div></div>
        <nav class="sidebar-nav">
            <a class="active" onclick="showStudentSubpage('dashboard')"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a>
            <a onclick="showStudentSubpage('schedule')"><i class="fas fa-calendar-alt"></i> جدول المحاضرات</a>
            <a onclick="showStudentSubpage('results')"><i class="fas fa-chart-bar"></i> النتائج</a>
            <a onclick="showStudentSubpage('payment')"><i class="fas fa-credit-card"></i> الدفع الإلكتروني</a>
            <a href="https://t.me/ahmedalfatih7" target="_blank"><i class="fab fa-telegram-plane"></i> بوت تيليجرام</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="top-bar"><h2 id="studentPageTitle">لوحة التحكم</h2><a href="logout.php" class="btn btn-back btn-sm"><i class="fas fa-sign-out-alt"></i> خروج</a></div>
        <div class="page-content" id="studentContent"></div>
    </main>
</div>
<footer class="footer">&copy; جميع الحقوق محفوظة - جامعة الرباط الوطني <span>2026</span></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
<script>
window.currentUserId = <?php echo json_encode($user['id']); ?>;
showStudentSubpage('dashboard');
</script>
</body>
</html>