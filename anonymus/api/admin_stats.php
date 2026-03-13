<?php
include '../auth.php';
include '../../includes/db.php';

header('Content-Type: application/json');

$payment_count = $pdo->query("SELECT COUNT(*) FROM payment_requests WHERE is_admin_seen = 0 AND status = 'pending'")->fetchColumn();
$report_count = $pdo->query("SELECT COUNT(*) FROM reports WHERE is_admin_seen = 0 AND status = 'pending'")->fetchColumn();

echo json_encode([
    'payments' => (int)$payment_count,
    'reports' => (int)$report_count
]);
