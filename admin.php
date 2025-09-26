<?php
require_once 'includes/auth.php';

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: pages/login.php');
    exit;
}

require_once 'includes/functions.php';
$functions = new Functions();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل مدیریت - سرویس L2TP</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="admin-header">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
                <span>پنل مدیریت L2TP</span>
            </div>
            <nav>
                <ul>
                    <li><a href="admin.php?tab=dashboard" class="<?php echo ($_GET['tab'] ?? 'dashboard') == 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> داشبورد
                    </a></li>
                    <li><a href="admin.php?tab=users" class="<?php echo ($_GET['tab'] ?? '') == 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> کاربران
                    </a></li>
                    <li><a href="admin.php?tab=servers" class="<?php echo ($_GET['tab'] ?? '') == 'servers' ? 'active' : ''; ?>">
                        <i class="fas fa-server"></i> سرورها
                    </a></li>
                    <li><a href="admin.php?tab=subscriptions" class="<?php echo ($_GET['tab'] ?? '') == 'subscriptions' ? 'active' : ''; ?>">
                        <i class="fas fa-key"></i> اشتراک‌ها
                    </a></li>
                    <li><a href="admin.php?tab=payments" class="<?php echo ($_GET['tab'] ?? '') == 'payments' ? 'active' : ''; ?>">
                        <i class="fas fa-credit-card"></i> پرداخت‌ها
                    </a></li>
                    <li><a href="admin.php?tab=settings" class="<?php echo ($_GET['tab'] ?? '') == 'settings' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> تنظیمات
                    </a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> خروج</a></li>
                </ul>
            </nav>
        </header>

        <main class="admin-main">
            <?php
            $tab = $_GET['tab'] ?? 'dashboard';
            switch ($tab) {
                case 'dashboard':
                    include 'pages/admin/dashboard.php';
                    break;
                case 'users':
                    include 'pages/admin/users.php';
                    break;
                case 'servers':
                    include 'pages/admin/servers.php';
                    break;
                case 'subscriptions':
                    include 'pages/admin/subscriptions.php';
                    break;
                case 'payments':
                    include 'pages/admin/payments.php';
                    break;
                case 'settings':
                    include 'pages/admin/settings.php';
                    break;
                default:
                    include 'pages/admin/dashboard.php';
            }
            ?>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>
