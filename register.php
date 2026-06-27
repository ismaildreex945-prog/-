<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['regName'] ?? '');
    $email = trim($_POST['regEmail'] ?? '');
    $phone = trim($_POST['regPhone'] ?? '');
    $password = $_POST['regPassword'] ?? '';
    $confirmPassword = $_POST['regConfirmPassword'] ?? '';
    $userType = $_POST['userType'] ?? 'student';
    $universityId = trim($_POST['regStudentId'] ?? '');
    $academicLevel = trim($_POST['regAcademicLevel'] ?? '');
    $specialization = trim($_POST['regSpecialization'] ?? '');
    $degree = trim($_POST['regDegree'] ?? '');
    $experienceYears = trim($_POST['regExperience'] ?? '');
    $exactSpecialization = trim($_POST['regExactSpecialization'] ?? '');
    $jobNumber = trim($_POST['regJobNumber'] ?? '');

    if ($fullName === '') {
        $errors[] = 'الاسم الكامل مطلوب';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'يرجى إدخال بريد إلكتروني صحيح';
    }
    if ($phone === '') {
        $errors[] = 'رقم الهاتف مطلوب';
    }
    if (strlen($password) < 8) {
        $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'كلمتا المرور غير متطابقتين';
    }
    if (!in_array($userType, ['student', 'doctor', 'registrar'], true)) {
        $errors[] = 'نوع المستخدم غير صالح';
    }
    if ($userType === 'student' && $universityId !== '' && !preg_match('/^\d{7}$/', $universityId)) {
        $errors[] = 'رقم الجامعة يجب أن يتكون من 7 أرقام';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'هذا البريد مستخدم مسبقاً';
        } else {
            $success = registerUser(
                $fullName,
                $email,
                $phone,
                $userType,
                $password,
                $universityId !== '' ? $universityId : null,
                $academicLevel !== '' ? $academicLevel : null,
                $specialization !== '' ? $specialization : null,
                $degree !== '' ? $degree : null,
                $experienceYears !== '' ? (int) $experienceYears : null,
                $exactSpecialization !== '' ? $exactSpecialization : null,
                $jobNumber !== '' ? $jobNumber : null
            );

            if ($success) {
                $_SESSION['auth_message'] = 'تم إنشاء الحساب بنجاح، يمكنك تسجيل الدخول الآن';
                header('Location: login.php');
                exit;
            }

            $errors[] = 'فشل إنشاء الحساب، يرجى المحاولة مرة أخرى';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب - المساعد الذكي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar"><div class="container"><a class="nav-brand" href="index.php"><div class="logo"><i class="fas fa-robot"></i></div>المساعد الذكي</a><div class="d-flex gap-2"><a href="index.php" class="btn btn-back"><i class="fas fa-arrow-right"></i> الرئيسية</a><a href="login.php" class="btn btn-outline"><i class="fas fa-sign-in-alt"></i> دخول</a></div></div></nav>
    <div class="auth-section"><div class="auth-card wide"><div class="auth-logo"><i class="fas fa-user-plus"></i></div><h2 class="auth-title">إنشاء حساب جديد</h2><?php if (!empty($errors)): ?><div class="alert alert-danger mt-3" role="alert"><?php echo htmlspecialchars(implode('<br>', $errors), ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?><div class="user-type-selector" id="userTypeSelector"><div class="user-type-card active" data-type="student"><i class="fas fa-user-graduate"></i><strong>طالب</strong></div><div class="user-type-card" data-type="doctor"><i class="fas fa-chalkboard-teacher"></i><strong>دكتور</strong></div><div class="user-type-card" data-type="registrar"><i class="fas fa-user-tie"></i><strong>مسجل</strong></div></div><form id="registerForm" action="register.php" method="post" novalidate><input type="hidden" name="userType" id="userTypeInput" value="student"><div class="form-row"><div class="form-group"><label><i class="fas fa-user"></i> الاسم الكامل</label><div class="input-wrapper"><span class="input-icon"><i class="fas fa-user"></i></span><input type="text" id="regName" name="regName" required></div><div class="error-message" id="regNameError"></div></div><div class="form-group"><label><i class="fas fa-envelope"></i> البريد الإلكتروني</label><div class="input-wrapper"><span class="input-icon"><i class="fas fa-envelope"></i></span><input type="email" id="regEmail" name="regEmail" required></div><div class="error-message" id="regEmailError"></div></div></div><div class="form-group"><label><i class="fas fa-mobile-alt"></i> رقم الهاتف</label><div class="phone-wrapper"><input type="tel" class="form-control" id="regPhone" name="regPhone" required><div class="phone-prefix">249+</div></div><div class="error-message" id="regPhoneError"></div></div><div id="studentFields" class="hidden-fields show"><div class="form-row"><div class="form-group"><label><i class="fas fa-id-card"></i> رقم الجامعة</label><input type="text" class="form-control" id="regStudentId" name="regStudentId"><div class="error-message" id="regStudentIdError"></div></div><div class="form-group"><label><i class="fas fa-layer-group"></i> المستوى</label><select class="form-select" name="regAcademicLevel"><option>الأول</option><option>الثاني</option><option>الثالث</option><option>الرابع</option></select></div></div><div class="form-group"><label><i class="fas fa-graduation-cap"></i> التخصص</label><select class="form-select" name="regSpecialization"><option>بكالوريوس علوم الحاسوب</option><option>بكالوريوس تقنية المعلومات</option></select></div></div><div id="doctorRegistrarFields" class="hidden-fields"><div class="form-row"><div class="form-group"><label><i class="fas fa-certificate"></i> الدرجة العلمية</label><select class="form-select" name="regDegree"><option>بكالوريوس</option><option>ماجستير</option><option>دكتوراه</option></select></div><div class="form-group"><label><i class="fas fa-briefcase"></i> سنوات الخبرة</label><input type="number" class="form-control" id="regExperience" name="regExperience" min="0"><div class="error-message" id="regExperienceError"></div></div></div><div class="form-row"><div class="form-group"><label><i class="fas fa-microscope"></i> التخصص الدقيق</label><input type="text" class="form-control" name="regExactSpecialization"></div><div class="form-group"><label><i class="fas fa-hashtag"></i> الرقم الوظيفي</label><input type="text" class="form-control" name="regJobNumber"></div></div></div><div class="form-row"><div class="form-group"><label><i class="fas fa-lock"></i> كلمة المرور</label><div class="input-wrapper"><span class="input-icon"><i class="fas fa-lock"></i></span><input type="password" id="regPassword" name="regPassword" required><span class="toggle-password" onclick="togglePassword('regPassword',this)"><i class="fas fa-eye"></i></span></div><div class="error-message" id="regPasswordError"></div></div><div class="form-group"><label><i class="fas fa-check-circle"></i> تأكيد كلمة المرور</label><div class="input-wrapper"><span class="input-icon"><i class="fas fa-check-circle"></i></span><input type="password" id="regConfirmPassword" name="regConfirmPassword" required><span class="toggle-password" onclick="togglePassword('regConfirmPassword',this)"><i class="fas fa-eye"></i></span></div><div class="error-message" id="regConfirmPasswordError"></div></div></div><button type="submit" class="btn-submit"><i class="fas fa-user-plus ms-2"></i> إنشاء الحساب</button></form><p class="text-center mt-3">لديك حساب؟ <a href="login.php" class="auth-link">تسجيل الدخول</a></p></div></div>
    <footer class="footer">&copy; جميع الحقوق محفوظة - جامعة الرباط الوطني <span>2026</span></footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>