<?php
include '../includes/db.php';
header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim(htmlspecialchars($_GET['q'])) : '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT title_tr, title_en, (views) as popularity FROM videos WHERE title_tr LIKE ? OR title_en LIKE ? ORDER BY popularity DESC LIMIT 5");
$stmt->execute(["%$q%", "%$q%"]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$suggestions = [];
foreach ($results as $row) {
    $suggestions[] = [
        'title' => $row['title_tr']
    ];
}

echo json_encode($suggestions);
?>
