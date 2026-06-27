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

$roleLabel = 'مسجل';
$pendingPayments = getPendingPayments();
$pendingPaymentsJson = json_encode($pendingPayments, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$pendingCount = count($pendingPayments);
$studentCount = getUserCountByType('student');
$doctorCount = getUserCountByType('doctor');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - مسجل | المساعد الذكي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header"><div class="sidebar-logo"><i class="fas fa-robot"></i></div><div><strong>المساعد الذكي</strong><br><small>جامعة الرباط الوطني</small></div></div>
        <div class="sidebar-user"><div class="user-avatar registrar"><i class="fas fa-user-tie"></i></div><div><strong><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br><small><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></small></div></div>
        <nav class="sidebar-nav">
            <a class="active" onclick="showRegistrarSubpage('dashboard')"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a>
            <a onclick="showRegistrarSubpage('payments')"><i class="fas fa-check-circle"></i> التحقق من الدفع</a>
            <a onclick="showRegistrarSubpage('reports')"><i class="fas fa-print"></i> طباعة التقارير</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </nav>
    </aside>
    <main class="main-content">
        <div class="top-bar"><h2 id="registrarPageTitle">لوحة التحكم - مسجل</h2><a href="logout.php" class="btn btn-back btn-sm"><i class="fas fa-sign-out-alt"></i> خروج</a></div>
        <div class="page-content" id="registrarContent">
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div><div><h4><?php echo $studentCount; ?></h4><small>طالب</small></div></div>
                <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-chalkboard-teacher"></i></div><div><h4><?php echo $doctorCount; ?></h4><small>دكتور</small></div></div>
                <div class="stat-card"><div class="stat-icon green"><i class="fas fa-check-circle"></i></div><div><h4><?php echo $pendingCount; ?></h4><small>دفعات معلقة</small></div></div>
            </div>
            <div class="card">
                <h3>طلبات الدفع المعلقة</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>الطالب</th><th>الرقم الجامعي</th><th>رقم العملية</th><th>المبلغ</th><th>التاريخ</th><th>الإيصال</th><th>الحالة</th></tr></thead>
                        <tbody>
                            <?php foreach ($pendingPayments as $payment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['university_id'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['transaction_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($payment['amount'], ENT_QUOTES, 'UTF-8'); ?> ج.س</td>
                                    <td><?php echo htmlspecialchars($payment['payment_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php if (!empty($payment['receipt_image'])): ?><a href="<?php echo htmlspecialchars($payment['receipt_image'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">عرض</a><?php else: ?>- <?php endif; ?></td>
                                    <td><span class="badge badge-warning">قيد المراجعة</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<footer class="footer">&copy; جميع الحقوق محفوظة - جامعة الرباط الوطني <span>2026</span></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
<script>
window.registrarPendingCount = <?php echo json_encode($pendingCount); ?>;
window.registrarStudentCount = <?php echo json_encode($studentCount); ?>;
window.registrarDoctorCount = <?php echo json_encode($doctorCount); ?>;
window.registrarPayments = <?php echo $pendingPaymentsJson; ?>;
showRegistrarSubpage('dashboard');
</script>
</body>
</html>