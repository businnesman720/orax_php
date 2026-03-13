<?php
session_start();
include '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid method']);
    exit;
}

$video_id = isset($_POST['video_id']) ? (int)$_POST['video_id'] : null;
$report_type_id = isset($_POST['report_type_id']) ? (int)$_POST['report_type_id'] : null;
$description = isset($_POST['description']) ? htmlspecialchars(trim($_POST['description'])) : '';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if (!$video_id || !$report_type_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO reports (video_id, user_id, report_type_id, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$video_id, $user_id, $report_type_id, $description]);
    
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
