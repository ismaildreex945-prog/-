<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

$errors = [];
$message = '';

if (!empty($_SESSION['auth_message'])) {
    $message = $_SESSION['auth_message'];
    unset($_SESSION['auth_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['loginEmail'] ?? '');
    $password = $_POST['loginPassword'] ?? '';

    if ($email === '') {
        $errors[] = 'البريد الإلكتروني مطلوب';
    }
    if ($password === '') {
        $errors[] = 'كلمة المرور مطلوبة';
    }

    if (empty($errors)) {
        $user = loginUser($email, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];

            if ($user['user_type'] === 'student') {
                header('Location: student.php');
            } elseif ($user['user_type'] === 'doctor') {
                header('Location: doctor.php');
            } else {
                header('Location: registrar.php');
            }
            exit;
        }

        $errors[] = 'بيانات الدخول غير صحيحة';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - المساعد الذكي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar"><div class="container"><a class="nav-brand" href="index.php"><div class="logo"><i class="fas fa-robot"></i></div>المساعد الذكي</a><div class="d-flex gap-2"><a href="index.php" class="btn btn-back"><i class="fas fa-arrow-right"></i> الرئيسية</a><a href="register.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> حساب جديد</a></div></div></nav>
    <div class="auth-section"><div class="auth-card"><div class="auth-logo"><i class="fas fa-lock-open"></i></div><h2 class="auth-title">تسجيل الدخول</h2><?php if (!empty($message)): ?><div class="alert alert-success mt-3" role="alert"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?><?php if (!empty($errors)): ?><div class="alert alert-danger mt-3" role="alert"><?php echo htmlspecialchars(implode('<br>', $errors), ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?><form id="loginForm" action="login.php" method="post" novalidate><div class="form-group"><label><i class="fas fa-envelope"></i> البريد الإلكتروني</label><div class="input-wrapper"><span class="input-icon"><i class="fas fa-envelope"></i></span><input type="email" id="loginEmail" name="loginEmail" placeholder="example@ribat.edu.sd" required></div><div class="error-message" id="loginEmailError"></div></div><div class="form-group"><label><i class="fas fa-lock"></i> كلمة المرور</label><div class="input-wrapper"><span class="input-icon"><i class="fas fa-lock"></i></span><input type="password" id="loginPassword" name="loginPassword" placeholder="********" required><span class="toggle-password" onclick="togglePassword('loginPassword',this)"><i class="fas fa-eye"></i></span></div><div class="error-message" id="loginPasswordError"></div></div><button type="submit" class="btn-submit"><i class="fas fa-sign-in-alt ms-2"></i> تسجيل الدخول</button></form><p class="text-center mt-3">ليس لديك حساب؟ <a href="register.php" class="auth-link">إنشاء حساب جديد</a></p></div></div>
    <footer class="footer">&copy; جميع الحقوق محفوظة - جامعة الرباط الوطني <span>2026</span></footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>