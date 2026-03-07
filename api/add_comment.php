<?php
include '../includes/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_text']) && isset($_SESSION['user_id'])) {
    $video_id = (int)$_POST['video_id'];
    $user_id = $_SESSION['user_id'];
    $text = htmlspecialchars($_POST['comment_text']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    $stmt = $pdo->prepare("INSERT INTO comments (video_id, user_id, parent_id, comment_text) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$video_id, $user_id, $parent_id, $text])) {
        $new_id = $pdo->lastInsertId();
        
        $username = $_SESSION['username'];
        $initial = strtoupper(mb_substr($username, 0, 1));
        $date = date('d.m.Y');
        
        echo json_encode([
            'status' => 'success',
            'id' => $new_id,
            'initial' => $initial,
            'username' => htmlspecialchars($username),
            'date' => $date,
            'text' => $text,
            'parent_id' => $parent_id
        ]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
