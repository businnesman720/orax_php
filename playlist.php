<?php
session_start();
include_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$playlist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

// Listeden Video Kaldırma
if (isset($_GET['remove_video'])) {
    $vid_to_remove = (int)$_GET['remove_video'];
    $stmt = $pdo->prepare("DELETE FROM playlist_videos WHERE playlist_id = ? AND video_id = ?");
    $stmt->execute([$playlist_id, $vid_to_remove]);
    header("Location: playlist.php?id=$playlist_id");
    exit;
}

// Liste Silme
if (isset($_GET['delete_playlist'])) {
    $stmt = $pdo->prepare("DELETE FROM playlists WHERE id = ? AND user_id = ?");
    $stmt->execute([$playlist_id, $user_id]);
    header("Location: index.php?msg=deleted");
    exit;
}

include 'includes/header.php';

// Oynatma Listesi Bilgilerini Çek
$stmt = $pdo->prepare("SELECT * FROM playlists WHERE id = ? AND user_id = ?");
$stmt->execute([$playlist_id, $user_id]);
$playlist = $stmt->fetch();

if (!$playlist) {
    echo "<div class='container'><h2 style='margin-top: 5rem; text-align: center;'>Oynatma listesi bulunamadı veya erişim yetkiniz yok.</h2></div>";
    include 'includes/footer.php';
    exit;
}

// Listedeki Videoları Çek
$stmt = $pdo->prepare("
    SELECT v.*, pv.added_at 
    FROM playlist_videos pv 
    JOIN videos v ON pv.video_id = v.id 
    WHERE pv.playlist_id = ? 
    ORDER BY pv.added_at DESC
");
$stmt->execute([$playlist_id]);
$videos = $stmt->fetchAll();

$texts_pl = [
    'tr' => [
        'empty' => 'Bu listede henüz video yok.',
        'remove' => 'Listeden Kaldır',
        'delete_pl' => 'Listeyi Sil',
        'total' => 'video',
        'confirm_delete' => 'Bu oynatma listesini tamamen silmek istediğinizden emin misiniz?'
    ],
    'en' => [
        'empty' => 'No videos in this playlist yet.',
        'remove' => 'Remove from Playlist',
        'delete_pl' => 'Delete Playlist',
        'total' => 'videos',
        'confirm_delete' => 'Are you sure you want to delete this playlist?'
    ]
];
$pt = $texts_pl[$lang];
?>

<div class="container animate-fade" style="padding-top: 2rem;">
    <div class="playlist-header-box">
        <div class="playlist-info">
            <div class="playlist-cover">
                <?php if(!empty($videos)): ?>
                    <img src="<?php echo $videos[0]['thumbnail'] ?: 'https://picsum.photos/seed/pl'.$playlist_id.'/400/225'; ?>">
                <?php else: ?>
                    <div class="empty-cover"><i class="fas fa-list"></i></div>
                <?php endif; ?>
                <div class="play-all-overlay"><i class="fas fa-play"></i></div>
            </div>
            <div class="playlist-details">
                <h1><?php echo htmlspecialchars($playlist['name']); ?></h1>
                <p><?php echo $_SESSION['username']; ?> • <?php echo count($videos); ?> <?php echo $pt['total']; ?></p>
                <div class="playlist-actions">
                    <button class="action-btn" onclick="confirmDeletePlaylist()"><i class="fas fa-trash"></i> <?php echo $pt['delete_pl']; ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="video-grid" style="margin-top: 3rem;">
        <?php if(empty($videos)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 5rem; opacity: 0.3;">
                <i class="fas fa-folder-open" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                <p><?php echo $pt['empty']; ?></p>
            </div>
        <?php else: ?>
            <?php foreach($videos as $vid): 
                $title = ($lang == 'tr') ? $vid['title_tr'] : $vid['title_en'];
            ?>
                <div class="video-card-wrapper" style="position: relative;">
                    <a href="video.php?id=<?php echo $vid['id']; ?>" class="video-card-link" style="text-decoration: none; color: inherit;">
                        <div class="video-card animate-fade">
                            <div class="thumbnail-container">
                                <img src="<?php echo $vid['thumbnail'] ?: 'https://picsum.photos/seed/'.$vid['id'].'/400/225'; ?>" alt="Thumbnail">
                                <div class="duration"><?php echo rand(5, 45); ?>:<?php echo rand(10, 59); ?></div>
                                <div class="play-overlay"><i class="fas fa-play"></i></div>
                            </div>
                            <div class="video-info">
                                <h3 class="video-title"><?php echo htmlspecialchars($title); ?></h3>
                                <div class="video-meta">
                                    <span><i class="fas fa-eye"></i> <?php echo number_format($vid['views']); ?></span>
                                    <span><?php echo date('d.m.Y', strtotime($vid['added_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                    <a href="playlist.php?id=<?php echo $playlist_id; ?>&remove_video=<?php echo $vid['id']; ?>" class="remove-btn-mini" title="<?php echo $pt['remove']; ?>">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDeletePlaylist() {
    showConfirmDialog(
        "<?php echo $pt['delete_pl']; ?>",
        "<?php echo $pt['confirm_delete']; ?>",
        () => {
            window.location.href = `playlist.php?id=<?php echo $playlist_id; ?>&delete_playlist=1`;
        }
    );
}
</script>

<style>
    .playlist-header-box { background: linear-gradient(180deg, rgba(211,47,47,0.2) 0%, rgba(15,15,15,1) 100%); padding: 3rem; border-radius: 24px; border: 1px solid rgba(255,255,255,0.05); }
    .playlist-info { display: flex; gap: 2rem; align-items: flex-end; }
    .playlist-cover { width: 280px; aspect-ratio: 16/9; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.5); position: relative; cursor: pointer; }
    .playlist-cover img { width: 100%; height: 100%; object-fit: cover; }
    .empty-cover { width: 100%; height: 100%; background: #222; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: #444; }
    .play-all-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: none; align-items: center; justify-content: center; font-size: 3rem; transition: 0.3s; }
    .playlist-cover:hover .play-all-overlay { display: flex; }
    .playlist-details h1 { font-size: 3rem; font-weight: 900; margin-bottom: 0.5rem; }
    .playlist-details p { opacity: 0.6; font-weight: 600; margin-bottom: 1.5rem; }
    .playlist-actions { display: flex; gap: 1rem; }
    .remove-btn-mini { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; z-index: 10; text-decoration: none; }
    .video-card-wrapper:hover .remove-btn-mini { opacity: 1; }
    .remove-btn-mini:hover { background: var(--primary-red); transform: scale(1.1); }
    
    @media (max-width: 768px) {
        .playlist-info { flex-direction: column; align-items: center; text-align: center; }
        .playlist-cover { width: 100%; max-width: 350px; }
        .playlist-details h1 { font-size: 2rem; }
    }
</style>

<?php include 'includes/footer.php'; ?>
