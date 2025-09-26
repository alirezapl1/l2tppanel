<?php
require_once '../includes/auth.php';

if ($auth->isLoggedIn()) {
    header('Location: ../user.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    $result = $auth->login($email, $password);
    if ($result['success']) {
        if ($result['is_admin']) {
            header('Location: ../admin.php');
        } else {
            header('Location: ../user.php');
        }
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود - سرویس L2TP</title>
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
                    <span>ورود به سرویس L2TP</span>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>ایمیل</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>رمز عبور</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">ورود</button>
                </form>
                
                <div class="auth-links">
                    <a href="register.php">حساب کاربری ندارید؟ ثبت نام کنید</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
