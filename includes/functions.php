<?php
require_once 'config/database.php';

class Functions {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    // دریافت آمار کلی
    public function getStats() {
        $stats = [];
        
        // تعداد کاربران فعال
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE is_active = TRUE");
        $stmt->execute();
        $stats['total_users'] = $stmt->fetchColumn();
        
        // تعداد سرورهای فعال
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM servers WHERE status = 'active'");
        $stmt->execute();
        $stats['active_servers'] = $stmt->fetchColumn();
        
        // تعداد اشتراک‌های فعال
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'");
        $stmt->execute();
        $stats['active_subscriptions'] = $stmt->fetchColumn();
        
        // درآمد ماه جاری
        $stmt = $this->pdo->prepare("SELECT SUM(amount) FROM payments 
                                   WHERE status = 'completed' 
                                   AND MONTH(created_at) = MONTH(CURRENT_DATE)");
        $stmt->execute();
        $stats['monthly_income'] = $stmt->fetchColumn() ?: 0;
        
        return $stats;
    }
    
    // ایجاد اشتراک جدید
    public function createSubscription($userId, $planType, $serverId = null) {
        try {
            // یافتن سرور مناسب
            if (!$serverId) {
                $stmt = $this->pdo->prepare("SELECT id FROM servers WHERE status = 'active' 
                                           AND current_users < max_users ORDER BY current_users ASC LIMIT 1");
                $stmt->execute();
                $serverId = $stmt->fetchColumn();
                
                if (!$serverId) {
                    return ['success' => false, 'message' => 'هیچ سرور فعالی با ظرفیت خالی یافت نشد'];
                }
            }
            
            // تعیین مدت زمان بر اساس نوع پلن
            $durations = [
                'monthly' => 30,
                '3months' => 90,
                '6months' => 180,
                'yearly' => 365
            ];
            
            $duration = $durations[$planType] ?? 30;
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime("+$duration days"));
            
            // تولید نام کاربری و رمز عبور
            $username = $this->generateUsername();
            $password = $this->generatePassword();
            
            // ایجاد اشتراک در دیتابیس
            $stmt = $this->pdo->prepare("INSERT INTO subscriptions 
                (user_id, server_id, plan_type, username, password, start_date, end_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([$userId, $serverId, $planType, $username, $password, $startDate, $endDate]);
            $subscriptionId = $this->pdo->lastInsertId();
            
            // ایجاد کاربر روی سرور L2TP
            require_once 'l2tp-installer.php';
            $installer = new L2TPInstaller();
            $result = $installer->createUser($serverId, $username, $password);
            
            if (!$result['success']) {
                // حذف اشتراک اگر ایجاد کاربر روی سرور失敗 شد
                $this->pdo->prepare("DELETE FROM subscriptions WHERE id = ?")->execute([$subscriptionId]);
                return $result;
            }
            
            // افزایش تعداد کاربران سرور
            $this->pdo->prepare("UPDATE servers SET current_users = current_users + 1 WHERE id = ?")
                ->execute([$serverId]);
            
            // ارسال ایمیل به کاربر
            $this->sendSubscriptionEmail($userId, $subscriptionId);
            
            return [
                'success' => true,
                'message' => 'اشتراک با موفقیت ایجاد شد',
                'subscription_id' => $subscriptionId,
                'username' => $username,
                'password' => $password
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // تولید نام کاربری منحصر به فرد
    private function generateUsername() {
        do {
            $username = 'user_' . substr(md5(uniqid()), 0, 8);
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM subscriptions WHERE username = ?");
            $stmt->execute([$username]);
        } while ($stmt->fetchColumn() > 0);
        
        return $username;
    }
    
    // تولید رمز عبور تصادفی
    private function generatePassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle($chars), 0, $length);
    }
    
    // ارسال ایمیل مشخصات اشتراک
    private function sendSubscriptionEmail($userId, $subscriptionId) {
        // دریافت اطلاعات کاربر و اشتراک
        $stmt = $this->pdo->prepare("SELECT u.email, u.full_name, s.*, sv.ip_address 
                                   FROM subscriptions s 
                                   JOIN users u ON s.user_id = u.id 
                                   JOIN servers sv ON s.server_id = sv.id 
                                   WHERE s.id = ?");
        $stmt->execute([$subscriptionId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $subject = "مشخصات اشتراک L2TP - پلن " . $this->getPlanName($data['plan_type']);
        $message = "سلام {$data['full_name']} عزیز،\n\n";
        $message .= "اشتراک شما با موفقیت فعال شد.\n\n";
        $message .= "مشخصات اتصال:\n";
        $message .= "آی‌پی سرور: {$data['ip_address']}\n";
        $message .= "نام کاربری: {$data['username']}\n";
        $message .= "رمز عبور: {$data['password']}\n";
        $message .= "پروتکل: L2TP\n";
        $message .= "تاریخ شروع: {$data['start_date']}\n";
        $message .= "تاریخ انقضا: {$data['end_date']}\n\n";
        $message .= "راهنمای اتصال:\n";
        $message .= "اندروید: Settings > Network & Internet > VPN\n";
        $message .= "آیفون: Settings > General > VPN\n";
        $message .= "ویندوز: Settings > Network & Internet > VPN\n\n";
        $message .= "با تشکر\nپشتیبانی سرویس L2TP";
        
        // ارسال ایمیل واقعی
        mail($data['email'], $subject, $message);
    }
    
    // دریافت نام فارسی پلن
    private function getPlanName($planType) {
        $names = [
            'monthly' => 'یک ماهه',
            '3months' => 'سه ماهه',
            '6months' => 'شش ماهه',
            'yearly' => 'یک ساله'
        ];
        
        return $names[$planType] ?? 'نامشخص';
    }
}
?>
