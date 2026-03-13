<?php
session_start();
// Klasör yapısını garantiye almak için tam yönlendirmeler
$base_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: $base_path/dashboard.php");
} else {
    header("Location: $base_path/login.php");
}
exit;
?>

