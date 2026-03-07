<?php
include '../includes/db.php';
session_start();
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'tr';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 30; // Kullanıcı isteği: En fazla 30 video
$offset = ($page - 1) * $limit;

// Mock Veri Üretimi (Gerçek projede DB'den çekilecek)
// SELECT * FROM videos LIMIT $offset, $limit
for($i = $offset + 1; $i <= $offset + $limit; $i++) {
    ?>
    <div class="video-card animate-fade">
        <div class="thumbnail-container">
            <img src="https://picsum.photos/seed/<?php echo $i + 100; ?>/400/225" alt="Video Thumbnail">
            <div class="duration"><?php echo rand(1, 20); ?>:<?php echo rand(10, 59); ?></div>
            <div class="play-overlay">
                <i class="fas fa-play"></i>
            </div>
        </div>
        <div class="video-info">
            <h3 class="video-title">Premium Video #<?php echo $i; ?> - High Quality</h3>
            <div class="video-meta">
                <span><i class="fas fa-eye"></i> <?php echo rand(1, 500); ?>K</span>
                <span><?php echo rand(1, 10); ?> <?php echo $lang == 'tr' ? 'gün önce' : 'days ago'; ?></span>
            </div>
        </div>
    </div>
    <?php
}
?>
