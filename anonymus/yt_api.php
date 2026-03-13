<?php
include 'auth.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $url = "https://www.youtube.com/watch?v=" . $id;

    // Fetch the YouTube page
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    $html = curl_exec($ch);
    curl_close($ch);

    $data = [
        'title' => '',
        'description' => '',
        'duration' => '0:00'
    ];

    // Extract Description (og:description)
    if (preg_match('/<meta name="description" content="([^"]+)"/', $html, $matches)) {
        $data['description'] = html_entity_decode($matches[1]);
    }

    // Extract Duration (ISO 8601 format: PT14M34S)
    if (preg_match('/"approxDurationMs":"(\d+)"/', $html, $matches)) {
        $ms = $matches[1];
        $total_seconds = floor($ms / 1000);
        $minutes = floor($total_seconds / 60);
        $seconds = $total_seconds % 60;
        $data['duration'] = $minutes . ":" . str_pad($seconds, 2, '0', STR_PAD_LEFT);
    } elseif (preg_match('/itemprop="duration" content="PT(\d+H)?(\d+M)?(\d+S)?"/', $html, $matches)) {
        // Fallback for duration
        $h = isset($matches[1]) ? (int)$matches[1] : 0;
        $m = isset($matches[2]) ? (int)$matches[2] : 0;
        $s = isset($matches[3]) ? (int)$matches[3] : 0;
        if ($h > 0) {
            $data['duration'] = $h . ":" . str_pad($m, 2, '0', STR_PAD_LEFT) . ":" . str_pad($s, 2, '0', STR_PAD_LEFT);
        } else {
            $data['duration'] = $m . ":" . str_pad($s, 2, '0', STR_PAD_LEFT);
        }
    }

    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>

