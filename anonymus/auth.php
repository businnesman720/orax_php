<?php
session_start();
// Admin oturum kontrolü - Eğer giriş yapılmamışsa login sayfasına yönlendir
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $base_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    header("Location: $base_path/login.php");
    exit;
}
?>
