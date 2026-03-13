<?php
include 'includes/db.php';
try {
    // Add is_admin_seen to payment_requests
    $pdo->exec("ALTER TABLE payment_requests ADD COLUMN is_admin_seen TINYINT(1) DEFAULT 0");
} catch(Exception $e) { /* already exists */ }

try {
    // Add is_admin_seen to reports
    $pdo->exec("ALTER TABLE reports ADD COLUMN is_admin_seen TINYINT(1) DEFAULT 0");
} catch(Exception $e) { /* already exists */ }

echo "Database updated for admin notifications.";
