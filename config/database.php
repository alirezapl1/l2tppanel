<?php
// تنظیمات پایگاه داده
define('DB_HOST', 'localhost');
define('DB_NAME', 'vpn_service');
define('DB_USER', 'username');
define('DB_PASS', 'password');
define('DB_CHARSET', 'utf8mb4');

// ایجاد اتصال به دیتابیس
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// ایجاد جداول لازم
function createTables() {
    $pdo = getDBConnection();
    
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            is_admin BOOLEAN DEFAULT FALSE,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS servers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL UNIQUE,
            ssh_username VARCHAR(100) NOT NULL,
            ssh_password VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive', 'installing') DEFAULT 'inactive',
            max_users INT DEFAULT 100,
            current_users INT DEFAULT 0,
            location VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            server_id INT NOT NULL,
            plan_type ENUM('monthly', '3months', '6months', 'yearly') NOT NULL,
            username VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            status ENUM('active', 'expired', 'suspended') DEFAULT 'active',
            traffic_limit BIGINT DEFAULT 0,
            used_traffic BIGINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (server_id) REFERENCES servers(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            subscription_id INT,
            amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('zarinpal', 'melat', 'saman') NOT NULL,
            transaction_id VARCHAR(255),
            status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) UNIQUE NOT NULL,
            setting_value TEXT NOT NULL,
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];
    
    foreach ($tables as $table) {
        $pdo->exec($table);
    }
    
    // ایجاد کاربر ادمین پیش‌فرض
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_admin = TRUE");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (email, password, full_name, is_admin) VALUES (?, ?, ?, TRUE)")
            ->execute(['admin@vpnservice.com', $hashedPassword, 'مدیر سیستم']);
    }
    
    // تنظیمات پیش‌فرض
    $defaultSettings = [
        ['site_name', 'سرویس L2TP حرفه‌ای', 'نام سایت'],
        ['site_url', 'https://yourdomain.com', 'آدرس سایت'],
        ['support_phone', '09123456789', 'شماره پشتیبانی'],
        ['support_telegram', '@VPNSupport', 'آیدی تلگرام'],
        ['support_email', 'support@yourdomain.com', 'ایمیل پشتیبانی'],
        ['monthly_price', '50000', 'قیمت اشتراک یک ماهه'],
        ['3months_price', '120000', 'قیمت اشتراک سه ماهه'],
        ['6months_price', '210000', 'قیمت اشتراک شش ماهه'],
        ['yearly_price', '360000', 'قیمت اشتراک یک ساله']
    ];
    
    foreach ($defaultSettings as $setting) {
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)")
            ->execute($setting);
    }
}

// اجرای ایجاد جداول هنگام اولین بار
createTables();
?>
