<?php
session_start();
require_once 'config/database.php';

class Auth {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    // ثبت نام کاربر جدید
    public function register($email, $password, $fullName, $phone = null) {
        // بررسی وجود ایمیل
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'این ایمیل قبلاً ثبت شده است'];
        }
        
        // هش کردن رمز عبور
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // ایجاد کاربر
        $stmt = $this->pdo->prepare("INSERT INTO users (email, password, full_name, phone) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$email, $hashedPassword, $fullName, $phone])) {
            // ارسال ایمیل خوشامدگویی
            $this->sendWelcomeEmail($email, $fullName);
            
            return ['success' => true, 'message' => 'ثبت نام با موفقیت انجام شد'];
        }
        
        return ['success' => false, 'message' => 'خطا در ثبت نام'];
    }
    
    // ورود کاربر
    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // به روزرسانی آخرین زمان ورود
            $this->pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?")
                ->execute([$user['id']]);
            
            return ['success' => true, 'is_admin' => $user['is_admin']];
        }
        
        return ['success' => false, 'message' => 'ایمیل یا رمز عبور نادرست است'];
    }
    
    // بررسی لاگین بودن کاربر
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // بررسی ادمین بودن
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
    }
    
    // خروج کاربر
    public function logout() {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    // ارسال ایمیل خوشامدگویی
    private function sendWelcomeEmail($email, $name) {
        $subject = "خوش آمدید به سرویس L2TP حرفه‌ای";
        $message = "سلام {$name} عزیز،\n\n";
        $message .= "ثبت نام شما در سرویس L2TP حرفه‌ای با موفقیت انجام شد.\n";
        $message .= "اکنون می‌توانید از طریق پنل کاربری خود اشتراک تهیه کنید.\n\n";
        $message .= "با تشکر\nپشتیبانی سرویس L2TP";
        
        // در اینجا کد ارسال ایمیل واقعی قرار می‌گیرد
        mail($email, $subject, $message);
    }
}

// ایجاد نمونه از کلاس Auth
$auth = new Auth();
?>
