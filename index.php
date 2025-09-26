<?php
require_once 'includes/auth.php';

if ($auth->isLoggedIn()) {
    if ($auth->isAdmin()) {
        header('Location: admin.php');
    } else {
        header('Location: user.php');
    }
    exit;
}

header('Location: pages/login.php');
?>
