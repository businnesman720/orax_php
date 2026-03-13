<?php
include '../includes/db.php';
session_start();
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'tr';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$sub_page = isset($_GET['sub_page']) ? (int)$_GET['sub_page'] : 0; // 0, 1, 2 (each carries 10 videos)
$q = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';

$main_limit = 30;
$chunk_size = 10;
$offset = (($page - 1) * $main_limit) + ($sub_page * $chunk_size);

// Query logic same as index.php
if ($q) {
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE title_tr LIKE ? OR title_en LIKE ? ORDER BY views DESC, created_at DESC LIMIT $chunk_size OFFSET $offset");
    $stmt->execute(["%$q%", "%$q%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM videos ORDER BY views DESC, created_at DESC LIMIT $chunk_size OFFSET $offset");
}

$videos = $stmt->fetchAll();

if (count($videos) > 0) {
    foreach($videos as $vid): 
        $display_title = ($lang == 'en' && !empty($vid['title_en'])) ? $vid['title_en'] : $vid['title_tr'];
        $thumb = $vid['thumbnail'];
        if (!$thumb) {
            $thumb_src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQwIiBoZWlnaHQ9IjM2MCIgdmlld0JveD0iMCAwIDY0MCAzNjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjY0MCIgaGVpZ2h0PSIzNjAiIGZpbGw9IiMxYTFhMWEiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZmlsbD0iIzhhOGE4YSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMjQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5PcmF4PC90ZXh0Pjwvc3ZnPg==';
        } else {
            $thumb_src = (strpos($thumb, 'http') === 0) ? $thumb : ltrim($thumb, '/');
        }
    ?>
        <a href="video.php?id=<?php echo $vid['id']; ?>" class="video-card-link" style="text-decoration: none; color: inherit;">
            <div class="video-card animate-fade">
                <div class="thumbnail-container" data-video-url="<?php echo $vid['video_url']; ?>" data-video-type="<?php echo $vid['video_type']; ?>">
                    <img src="<?php echo $thumb_src; ?>" alt="Thumbnail" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQwIiBoZWlnaHQ9IjM2MCIgdmlld0JveD0iMCAwIDY0MCAzNjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjY0MCIgaGVpZ2h0PSIzNjAiIGZpbGw9IiMxYTFhMWEiLz48L3N2Zz4=';">
                    
                    <?php if($vid['video_type'] == 'file' || $vid['video_type'] == 'url'): ?>
                        <video class="preview-video" muted playsinline loop preload="none"></video>
                    <?php endif; ?>

                    <div class="duration"><?php echo !empty($vid['duration']) ? $vid['duration'] : '00:00'; ?></div>
                    <div class="play-overlay"><i class="fas fa-play"></i></div>
                </div>
                <div class="video-info">
                    <h3 class="video-title"><?php echo htmlspecialchars($display_title); ?></h3>
                    <div class="video-meta">
                        <span><i class="fas fa-eye"></i> <?php echo number_format($vid['views']); ?></span>
                        <span><?php echo date('d.m.Y', strtotime($vid['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </a>
    <?php endforeach;
} else {
    // No more videos
    echo "";
}
?>
