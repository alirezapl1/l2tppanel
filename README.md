# l2tppanel
# راهنمای نصب کامل سیستم فروش اشتراک L2TP

## پیش‌نیازهای سرور

- PHP 7.4 یا بالاتر
- MySQL 5.7 یا بالاتر
- دسترسی SSH به سرورهای اوبونتو 22.04
- extensionهای PHP: `pdo_mysql`, `ssh2`, `openssl`

## مراحل نصب

### 1. آپلود فایل‌ها
تمام فایل‌ها را در هاست آپلود کنید.

### 2. تنظیمات پایگاه داده
فایل `config/database.php` را ویرایش کنید:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'vpn_service');
define('DB_USER', 'username');
define('DB_PASS', 'password');
