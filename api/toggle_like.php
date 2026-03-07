<?php
include '../includes/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['video_id']) && isset($_POST['action']) && isset($_SESSION['user_id'])) {
    $video_id = (int)$_POST['video_id'];
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'];
    $is_dislike = $action === 'dislike' ? 1 : 0;

    $check = $pdo->prepare("SELECT id, is_dislike FROM video_likes WHERE video_id = ? AND user_id = ?");
    $check->execute([$video_id, $user_id]);
    $existing = $check->fetch();
    
    if ($existing) {
        if ($existing['is_dislike'] == $is_dislike) {
            $pdo->prepare("DELETE FROM video_likes WHERE id = ?")->execute([$existing['id']]);
        } else {
            $pdo->prepare("UPDATE video_likes SET is_dislike = ? WHERE id = ?")->execute([$is_dislike, $existing['id']]);
        }
    } else {
        $pdo->prepare("INSERT INTO video_likes (video_id, user_id, is_dislike) VALUES (?, ?, ?)")->execute([$video_id, $user_id, $is_dislike]);
    }

    $l_count = $pdo->prepare("SELECT COUNT(*) FROM video_likes WHERE video_id = ? AND is_dislike = 0");
    $l_count->execute([$video_id]);
    $likes = $l_count->fetchColumn();

    $d_count = $pdo->prepare("SELECT COUNT(*) FROM video_likes WHERE video_id = ? AND is_dislike = 1");
    $d_count->execute([$video_id]);
    $dislikes = $d_count->fetchColumn();

    $choice = $pdo->prepare("SELECT is_dislike FROM video_likes WHERE video_id = ? AND user_id = ?");
    $choice->execute([$video_id, $user_id]);
    $res = $choice->fetch();
    $user_choice = null;
    if ($res) $user_choice = $res['is_dislike'] == 1 ? 'dislike' : 'like';

    echo json_encode([
        'status' => 'success',
        'likes' => $likes,
        'dislikes' => $dislikes,
        'user_choice' => $user_choice
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
