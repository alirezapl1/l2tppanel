<?php
require_once '../includes/auth.php';

if ($auth->isLoggedIn()) {
    header('Location: ../user.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $fullName = $_POST['full_name'];
    $phone = $_POST['phone'];
    
    // اعتبارسنجی
    if (empty($email) || empty($password) || empty($fullName)) {
        $error = 'پر کردن فیلدهای الزامی ضروری است';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'ایمیل وارد شده معتبر نیست';
    } elseif ($password !== $confirmPassword) {
        $error = 'رمز عبور و تکرار آن مطابقت ندارند';
    } elseif (strlen($password) < 6) {
        $error = 'رمز عبور باید حداقل ۶ کاراکتر باشد';
    } else {
        $result = $auth->register($email, $password, $fullName, $phone);
        if ($result['success']) {
            $success = $result['message'];
            header('Refresh: 3; url=login.php');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت نام - سرویس L2TP</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <div class="logo">
                    <i class="fas fa-shield-alt"></i>
                    <span>ثبت نام در سرویس L2TP</span>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>نام کامل *</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo $_POST['full_name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>ایمیل *</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>شماره تماس</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo $_POST['phone'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>رمز عبور *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>تکرار رمز عبور *</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">ثبت نام</button>
                </form>
                
                <div class="auth-links">
                    <a href="login.php">قبلاً ثبت نام کرده‌اید؟ وارد شوید</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
