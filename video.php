<?php
session_start();
include_once 'includes/db.php';
$video_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Oynatma Listesi Oluşturma / Ekleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['playlist_name']) && $user_id) {
    $name = htmlspecialchars(trim($_POST['playlist_name']));
    
    // AYNI İSİMLİ LİSTE KONTROLÜ
    $check = $pdo->prepare("SELECT id FROM playlists WHERE user_id = ? AND name = ?");
    $check->execute([$user_id, $name]);
    
    if ($check->fetch()) {
        // Zaten var, hata gönder
        $redirect = $video_id ? "video.php?id=$video_id&error=exists" : "index.php?error=exists";
        header("Location: $redirect");
    } else {
        $stmt = $pdo->prepare("INSERT INTO playlists (user_id, name) VALUES (?, ?)");
        $stmt->execute([$user_id, $name]);
        $playlist_id = $pdo->lastInsertId();
        
        // Eğer bir video sayfasındaysa videoyu hemen listeye ekle
        if ($video_id) {
            $pdo->prepare("INSERT INTO playlist_videos (playlist_id, video_id) VALUES (?, ?)")->execute([$playlist_id, $video_id]);
            header("Location: video.php?id=$video_id&msg=added");
        } else {
            header("Location: index.php?msg=playlist_created");
        }
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_playlist']) && $user_id && $video_id) {
    $pid = (int)$_POST['playlist_id'];
    
    // VİDEO ZATEN VAR MI KONTROLÜ
    $exists = $pdo->prepare("SELECT id FROM playlist_videos WHERE playlist_id = ? AND video_id = ?");
    $exists->execute([$pid, $video_id]);
    
    if ($exists->fetch()) {
        header("Location: video.php?id=$video_id&error=video_exists");
    } else {
        try {
            $pdo->prepare("INSERT INTO playlist_videos (playlist_id, video_id) VALUES (?, ?)")->execute([$pid, $video_id]);
            header("Location: video.php?id=$video_id&msg=added");
        } catch(Exception $e) { header("Location: video.php?id=$video_id"); }
    }
    exit;
}

include 'includes/header.php';

if (!$video_id) {
    header("Location: index.php");
    exit;
}

// Sayfa Verilerini Çek
$stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
$stmt->execute([$video_id]);
$video = $stmt->fetch();

// İzlenme sayısını artır
if ($video) {
    $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?")->execute([$video_id]);
    // Güncel veriyi tekrar çek ki ekranda hemen yansısın
    $stmt->execute([$video_id]);
    $video = $stmt->fetch();
}

// Like/Dislike Sayıları
$likes_count = $pdo->prepare("SELECT COUNT(*) FROM video_likes WHERE video_id = ? AND is_dislike = 0");
$likes_count->execute([$video_id]);
$l_count = $likes_count->fetchColumn();

$dislikes_count = $pdo->prepare("SELECT COUNT(*) FROM video_likes WHERE video_id = ? AND is_dislike = 1");
$dislikes_count->execute([$video_id]);
$d_count = $dislikes_count->fetchColumn();

// Kullanıcının mevcut seçimi
$user_choice = null;
if ($user_id) {
    $choice = $pdo->prepare("SELECT is_dislike FROM video_likes WHERE video_id = ? AND user_id = ?");
    $choice->execute([$video_id, $user_id]);
    $res = $choice->fetch();
    if ($res) $user_choice = $res['is_dislike'] == 1 ? 'dislike' : 'like';
}

// Kullanıcı Oynatma Listeleri
$user_playlists = [];
$video_in_playlists = []; // Hangi listelerde bu video var?
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM playlists WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_playlists = $stmt->fetchAll();
    
    // Mevcut videonun hangi listelerde olduğunu bul
    $stmt_check = $pdo->prepare("SELECT playlist_id FROM playlist_videos WHERE video_id = ?");
    $stmt_check->execute([$video_id]);
    $video_in_playlists = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
}

// Yorum POST ajax_comment.php üzerinden yapılıyor, buradan sildik.

// Yorum Silme Kontrolü (Aynı logic devam ediyor...)
if (isset($_GET['delete_comment']) && $user_id) {
    $comment_id = (int)$_GET['delete_comment'];
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    $stmt->execute([$comment_id, $user_id]);
    header("Location: video.php?id=$video_id#comments");
    exit;
}

// Tüm Yorumları Çek ve Hiyerarşik Yap (Hata düzeltilmiş versiyon)
$stmt = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.video_id = ? ORDER BY c.created_at DESC");
$stmt->execute([$video_id]);
$raw_comments = $stmt->fetchAll();
$comments_tree = []; $valid_comments_count = 0; $temp_replies = [];
foreach ($raw_comments as $c) {
    if ($c['parent_id'] === null) { $comments_tree[$c['id']] = $c; $comments_tree[$c['id']]['replies'] = []; $valid_comments_count++; }
    else { $temp_replies[] = $c; }
}
foreach ($temp_replies as $r) {
    if (isset($comments_tree[$r['parent_id']])) { $comments_tree[$r['parent_id']]['replies'][] = $r; $valid_comments_count++; }
}
$all_comments_count = $valid_comments_count;

// Önerilen Videolar (Aynı kategori veya rastgele)
$stmt_rec = $pdo->prepare("SELECT * FROM videos WHERE id != ? ORDER BY (category_id = ?) DESC, created_at DESC LIMIT 10");
$stmt_rec->execute([$video_id, $video['category_id'] ?? 0]);
$recommended_videos = $stmt_rec->fetchAll();

$lang_texts = [
    'tr' => [
        'comments' => 'Yorum', 'no_comments' => 'Henüz yorum yapılmamış.', 'write_comment' => 'Yorum yazın...', 'send' => 'Gönder',
        'must_login' => 'Giriş yapmalısınız.', 'views' => 'izlenme', 'recommended' => 'Sıradaki Videolar', 'reply' => 'Yanıtla',
        'cancel_reply' => 'İptal', 'delete_confirm_title' => 'Yorumu Sil', 'delete_confirm_msg' => 'Silmek istediğinize emin misiniz?',
        'share_title' => 'Videoyu Paylaş', 'playlist_title' => 'Listeye Ekle', 'create_playlist' => 'Yeni Liste Oluştur',
        'copied' => 'Link Kopyalandı!'
    ],
    'en' => [
        'comments' => 'Comments', 'no_comments' => 'No comments yet.', 'write_comment' => 'Add a comment...', 'send' => 'Post',
        'must_login' => 'Log in.', 'views' => 'views', 'recommended' => 'Up Next', 'reply' => 'Reply',
        'cancel_reply' => 'Cancel', 'delete_confirm_title' => 'Delete Comment', 'delete_confirm_msg' => 'Are you sure?',
        'share_title' => 'Share Video', 'playlist_title' => 'Add to Playlist', 'create_playlist' => 'Create New Playlist',
        'copied' => 'Link Copied!'
    ]
];
$vt = $lang_texts[$lang];
?>

<div class="video-page-wrapper">
    <div class="container">
        <div class="video-layout">
            <div class="video-primary">
                <div class="player-container animate-fade">
                    <?php if ($video['video_type'] == 'embed'): ?>
                        <?php 
                        $embed_url = $video['video_url'];
                        $yt_id = '';
                        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $embed_url, $matches)) {
                            $yt_id = $matches[1];
                        }
                        
                        if ($yt_id): ?>
                            <div id="player" data-plyr-provider="youtube" data-plyr-embed-id="<?php echo $yt_id; ?>"></div>
                        <?php else: ?>
                            <div id="player" class="plyr__video-embed">
                                <iframe src="<?php echo $embed_url; ?>" allowfullscreen allowtransparency allow="autoplay"></iframe>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php 
                        $v_url = $video['video_url'];
                        if (strpos($v_url, 'http') === false) {
                            $v_url = "stream.php?file=" . urlencode(ltrim($v_url, '/'));
                        }
                        
                        $video_thumb = $video['thumbnail'];
                        if ($video_thumb && strpos($video_thumb, 'http') === false) {
                            $video_thumb = ltrim($video_thumb, '/');
                        }
                        ?>
                        <video id="player" playsinline controls data-poster="<?php echo $video_thumb ?: ''; ?>" preload="auto">
                            <source src="<?php echo $v_url; ?>" type="video/mp4">
                        </video>
                    <?php endif; ?>
                </div>

                <div class="video-info-box">
                    <h1 class="video-main-title">
                        <?php 
                        $display_title = ($lang == 'en' && !empty($video['title_en'])) ? $video['title_en'] : $video['title_tr'];
                        echo htmlspecialchars($display_title); 
                        ?>
                    </h1>
                    <div class="video-meta-bar">
                        <div class="meta-left">
                            <span class="views-count"><?php echo number_format($video['views'] ?? 1400000); ?> <?php echo $vt['views']; ?></span>
                        </div>
                        <div class="meta-right">
                            <button class="action-btn toggle-like-btn <?php echo $user_choice == 'like' ? 'active-red' : ''; ?>" data-action="like" data-id="<?php echo $video_id; ?>">
                                <i class="fas fa-thumbs-up"></i>
                                <span class="like-counter" style="display:inline-block; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);"><?php echo $l_count; ?></span>
                            </button>
                            <button class="action-btn toggle-like-btn <?php echo $user_choice == 'dislike' ? 'active-red' : ''; ?>" data-action="dislike" data-id="<?php echo $video_id; ?>">
                                <i class="fas fa-thumbs-down"></i>
                                <span class="dislike-counter" style="display:inline-block; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);"><?php echo $d_count; ?></span>
                            </button>
                            <button class="action-btn" onclick="openShareModal()"><i class="fas fa-share"></i></button>
                            <button class="action-btn" onclick="openPlaylistModal()"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </div>

                <!-- ... Diğer içerikler (Aynı devam ediyor) ... -->

                <div class="video-desc-box expandable-desc">
                    <div class="desc-content-wrapper" id="desc-wrapper">
                        <div class="desc-content" id="video-desc">
                            <?php 
                            $display_desc = ($lang == 'en' && !empty($video['description_en'])) ? $video['description_en'] : $video['description_tr'];
                            echo nl2br(htmlspecialchars($display_desc)); 
                            ?>
                        </div>
                    </div>
                    <button class="desc-toggle-btn" id="desc-btn" onclick="toggleDesc(event)"><?php echo ($lang == 'tr' ? '...daha fazla' : '...show more'); ?></button>
                </div>

                <!-- Comments Section -->
                <section id="comments" class="comments-section">
                    <div class="comments-header">
                        <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 1.5rem;"><?php echo $all_comments_count; ?> <?php echo $vt['comments']; ?></h3>
                    </div>

                    <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="add-comment-box" id="main-comment-form">
                        <div class="user-avatar-small"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                        <form method="POST" class="comment-form">
                            <input type="text" name="comment_text" placeholder="<?php echo $vt['write_comment']; ?>" required>
                            <div class="form-btns">
                                <button type="submit" class="btn btn-primary btn-sm"><?php echo $vt['send']; ?></button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="login-prompt">
                        <a href="auth.php"><?php echo $vt['must_login']; ?></a>
                    </div>
                    <?php endif; ?>

                    <div class="comments-list" id="comments-container">
                        <?php if(empty($comments_tree)): ?>
                            <p class="empty-msg"><?php echo $vt['no_comments']; ?></p>
                        <?php else: ?>
                            <?php 
                            $idx = 0;
                            foreach($comments_tree as $comment): 
                                $idx++;
                            ?>
                            <div class="comment-item-wrapper animate-fade comment-node" data-index="<?php echo $idx; ?>" style="<?php echo $idx > 3 ? 'display: none;' : ''; ?>">
                                <div class="comment-item">
                                    <div class="user-avatar-small"><?php echo strtoupper(substr($comment['username'], 0, 1)); ?></div>
                                    <div class="comment-body">
                                        <div class="comment-user"><?php echo htmlspecialchars($comment['username']); ?> <span><?php echo date('d.m.Y', strtotime($comment['created_at'])); ?></span></div>
                                        <div class="comment-text"><?php echo htmlspecialchars($comment['comment_text']); ?></div>
                                        <div class="comment-actions">
                                            <i class="fas fa-thumbs-up"></i>
                                            <i class="fas fa-thumbs-down"></i>
                                            <span class="reply-trigger" data-id="<?php echo $comment['id']; ?>" data-mention="<?php echo htmlspecialchars($comment['username']); ?>"><?php echo $vt['reply']; ?></span>
                                            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $comment['user_id']): ?>
                                                <a href="javascript:void(0)" class="delete-link" onclick="confirmDelete(<?php echo $comment['id']; ?>)"><i class="fas fa-trash"></i></a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Reply Form Placeholder -->
                                        <div class="reply-form-container" id="reply-to-<?php echo $comment['id']; ?>"></div>

                                        <!-- Nested Replies -->
                                        <div class="replies-list" id="replies-for-<?php echo $comment['id']; ?>">
                                        <?php if(!empty($comment['replies'])): ?>
                                            <?php foreach($comment['replies'] as $reply): ?>
                                            <div class="comment-item reply-item">
                                                <div class="user-avatar-small"><?php echo strtoupper(substr($reply['username'], 0, 1)); ?></div>
                                                <div class="comment-body">
                                                    <div class="comment-user"><?php echo htmlspecialchars($reply['username']); ?> <span><?php echo date('d.m.Y', strtotime($reply['created_at'])); ?></span></div>
                                                    <div class="comment-text"><?php echo htmlspecialchars($reply['comment_text']); ?></div>
                                                    <div class="comment-actions">
                                                        <i class="fas fa-thumbs-up"></i>
                                                        <span class="reply-trigger" data-id="<?php echo $comment['id']; ?>" data-mention="<?php echo htmlspecialchars($reply['username']); ?>"><?php echo $vt['reply']; ?></span>
                                                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $reply['user_id']): ?>
                                                            <a href="javascript:void(0)" class="delete-link" onclick="confirmDelete(<?php echo $reply['id']; ?>)"><i class="fas fa-trash"></i></a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if(count($comments_tree) > 3): ?>
                    <div id="comments-pagination" style="text-align: center; margin: 3rem 0 5rem 0; padding-top: 1.5rem;">
                        <button id="btn-show-more" class="btn" style="background: rgba(211, 47, 47, 0.05); color: var(--primary-red); border-radius: 50px; padding: 0.9rem 2.5rem; font-weight: 800; cursor: pointer; border: 2px solid var(--primary-red); transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; font-size: 0.85rem;" onmouseover="this.style.background='var(--primary-red)'; this.style.color='white'; this.style.boxShadow='0 10px 25px rgba(211,47,47,0.4)'" onmouseout="this.style.background='rgba(211, 47, 47, 0.05)'; this.style.color='var(--primary-red)'; this.style.boxShadow='none'">
                            <?php echo ($lang == 'tr' ? 'Daha Fazla Göster' : 'Show More'); ?>
                        </button>
                        <button id="btn-show-less" class="btn" style="display: none; background: none; color: rgba(255,255,255,0.4); border: none; font-weight: 700; margin-left: 1rem; cursor: pointer; font-size: 0.9rem; text-decoration: underline;">
                            <?php echo ($lang == 'tr' ? 'Yorumları Gizle' : 'Hide Comments'); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="video-secondary" style="background: none; border: none; padding-top: 2rem;">
                <h3 class="side-title"><?php echo $vt['recommended']; ?></h3>
                <div class="recommended-grid">
                    <?php if(empty($recommended_videos)): ?>
                        <p style="opacity:0.3; font-size:0.8rem;">No recommendations yet.</p>
                    <?php else: ?>
                        <?php foreach($recommended_videos as $rv): 
                            $rv_title = ($lang == 'tr') ? $rv['title_tr'] : $rv['title_en'];
                        ?>
                        <a href="video.php?id=<?php echo $rv['id']; ?>" class="rec-item">
                            <div class="rec-thumb">
                                <img src="<?php echo $rv['thumbnail'] ? ltrim($rv['thumbnail'], '/') : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYwIiBoZWlnaHQ9IjkwIiB2aWV3Qm94PSIwIDAgMTYwIDkwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxNjAiIGhlaWdodD0iOTAiIGZpbGw9IiMxYTFhMWEiLz48L3N2Zz4='; ?>" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYwIiBoZWlnaHQ9IjkwIiB2aWV3Qm94PSIwIDAgMTYwIDkwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxNjAiIGhlaWdodD0iOTAiIGZpbGw9IiMxYTFhMWEiLz48L3N2Zz4=';">
                                <span class="dur"><?php echo rand(4, 20); ?>:<?php echo rand(10, 59); ?></span>
                            </div>
                            <div class="rec-meta">
                                <h4><?php echo htmlspecialchars($rv_title); ?></h4>
                                <p>Orax Premium</p>
                                <p><?php echo number_format($rv['views']); ?> <?php echo $vt['views']; ?></p>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</div>

<!-- Social Share Modal -->
<div class="orax-dialog-overlay" id="share-modal-overlay">
    <div class="orax-dialog" id="share-modal">
        <div class="dialog-header">
            <i class="fas fa-share-alt"></i>
            <span><?php echo $vt['share_title']; ?></span>
        </div>
        <div class="dialog-body">
            <div class="share-links">
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" target="_blank" class="share-icon tw"><i class="fab fa-twitter"></i></a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" target="_blank" class="share-icon fb"><i class="fab fa-facebook"></i></a>
                <a href="https://wa.me/?text=<?php echo urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" target="_blank" class="share-icon wa"><i class="fab fa-whatsapp"></i></a>
            </div>
            <div class="copy-link-box">
                <input type="text" value="http://<?php echo $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>" id="share-url-input" readonly>
                <button onclick="copyShareLink()"><?php echo ($lang == 'tr' ? 'Kopyala' : 'Copy'); ?></button>
            </div>
        </div>
        <div class="dialog-footer">
            <button class="btn-dialog btn-dialog-cancel" onclick="closeShareModal()"><?php echo $vt['cancel_reply']; ?></button>
        </div>
    </div>
</div>

<!-- Playlist Modal -->
<div class="orax-dialog-overlay" id="playlist-modal-overlay">
    <div class="orax-dialog" id="playlist-modal">
        <div class="dialog-header">
            <i class="fas fa-list"></i>
            <span><?php echo $vt['playlist_title']; ?></span>
        </div>
        <div class="dialog-body">
            <?php if(!$user_id): ?>
                <p style="text-align: center;"><?php echo $vt['must_login']; ?></p>
            <?php else: ?>
                <div class="existing-playlists">
                    <?php foreach($user_playlists as $pl): 
                        $is_added = in_array($pl['id'], $video_in_playlists);
                    ?>
                        <form method="POST" style="margin-bottom: 0.5rem;">
                            <input type="hidden" name="playlist_id" value="<?php echo $pl['id']; ?>">
                            <button type="submit" name="add_to_playlist" class="playlist-item-btn" <?php echo $is_added ? 'disabled' : ''; ?> style="<?php echo $is_added ? 'opacity: 0.5; cursor: default;' : ''; ?>">
                                <?php if($is_added): ?>
                                    <i class="fas fa-check" style="color: #4CAF50;"></i>
                                <?php else: ?>
                                    <i class="fas fa-plus"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($pl['name']); ?>
                                <?php if($is_added): ?>
                                    <span style="font-size: 0.7rem; margin-left: auto; opacity: 0.6;">(<?php echo ($lang == 'tr' ? 'Eklendi' : 'Added'); ?>)</span>
                                <?php endif; ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
                <hr style="opacity: 0.1; margin: 1.5rem 0;">
                <form method="POST" class="create-playlist-form">
                    <input type="text" name="playlist_name" placeholder="<?php echo $vt['create_playlist']; ?>" required>
                    <button type="submit" class="btn btn-primary btn-sm" style="margin-top: 10px; border-radius: 50px; width: 100%;"><?php echo $vt['send']; ?></button>
                </form>
            <?php endif; ?>
        </div>
        <div class="dialog-footer">
            <button class="btn-dialog btn-dialog-cancel" onclick="closePlaylistModal()"><?php echo $vt['cancel_reply']; ?></button>
        </div>
    </div>
</div>

<template id="reply-form-template">
    <div class="add-comment-box reply-box animate-fade" style="margin-top: 1rem;">
        <form method="POST" class="comment-form">
            <input type="hidden" name="parent_id" value="">
            <input type="text" name="comment_text" placeholder="<?php echo $vt['write_comment']; ?>" required autoFocus>
            <div class="form-btns">
                <button type="button" class="btn btn-dialog-cancel btn-sm cancel-reply-btn" style="background: none; border-radius: 50px;"><?php echo $vt['cancel_reply']; ?></button>
                <button type="submit" class="btn btn-primary btn-sm" style="border-radius: 50px;"><?php echo $vt['send']; ?></button>
            </div>
        </form>
    </div>
</template>

<script>
function openShareModal() { 
    const overlay = document.getElementById('share-modal-overlay');
    overlay.style.display = 'flex';
    setTimeout(() => document.getElementById('share-modal').classList.add('active'), 10);
}
function closeShareModal() {
    document.getElementById('share-modal').classList.remove('active');
    setTimeout(() => document.getElementById('share-modal-overlay').style.display = 'none', 300);
}
function copyShareLink() {
    const input = document.getElementById('share-url-input');
    input.select();
    document.execCommand('copy');
    alert("<?php echo $vt['copied']; ?>");
}

function openPlaylistModal() {
    const overlay = document.getElementById('playlist-modal-overlay');
    overlay.style.display = 'flex';
    setTimeout(() => document.getElementById('playlist-modal').classList.add('active'), 10);
}
function closePlaylistModal() {
    document.getElementById('playlist-modal').classList.remove('active');
    setTimeout(() => document.getElementById('playlist-modal-overlay').style.display = 'none', 300);
}

function confirmDelete(id) {
    showConfirmDialog(
        "<?php echo $vt['delete_confirm_title']; ?>",
        "<?php echo $vt['delete_confirm_msg']; ?>",
        () => {
            window.location.href = `video.php?id=<?php echo $video_id; ?>&delete_comment=${id}`;
        }
    );
}

document.addEventListener('submit', function(e) {
    if (e.target && e.target.classList.contains('comment-form')) {
        e.preventDefault();
        
        <?php if(!isset($_SESSION['user_id'])): ?>
            window.location.href = 'auth.php';
            return;
        <?php endif; ?>
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        if (!submitBtn) return;
        
        const originalText = submitBtn.innerText;
        submitBtn.innerText = '...';
        submitBtn.disabled = true;
        
        const formData = new FormData(form);
        formData.append('video_id', '<?php echo $video_id; ?>');
        
        fetch('api/add_comment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const isReply = data.parent_id !== null;
                const replyTrigger = !isReply ? `<span class="reply-trigger" data-id="${data.id}" data-mention="${data.username}"><?php echo $vt['reply']; ?></span>` : `<span class="reply-trigger" data-id="${data.parent_id}" data-mention="${data.username}"><?php echo $vt['reply']; ?></span>`;
                const deleteLink = `<a href="javascript:void(0)" class="delete-link" onclick="confirmDelete(${data.id})"><i class="fas fa-trash"></i></a>`;
                const replyContainers = !isReply ? `<div class="reply-form-container" id="reply-to-${data.id}"></div><div class="replies-list" id="replies-for-${data.id}"></div>` : '';

                const commentHtml = `
                    <div class="comment-item-wrapper animate-fade" style="margin-top:20px;">
                        <div class="comment-item ${isReply ? 'reply-item' : ''}">
                            <div class="user-avatar-small">${data.initial}</div>
                            <div class="comment-body">
                                <div class="comment-user">${data.username} <span>${data.date}</span></div>
                                <div class="comment-text">${data.text}</div>
                                <div class="comment-actions">
                                    <i class="fas fa-thumbs-up"></i>
                                    <i class="fas fa-thumbs-down"></i>
                                    ${replyTrigger}
                                    ${deleteLink}
                                </div>
                                ${replyContainers}
                            </div>
                        </div>
                    </div>
                `;
                
                if (isReply) {
                    const repliesList = document.getElementById(`replies-for-${data.parent_id}`);
                    if (repliesList) {
                        repliesList.insertAdjacentHTML('beforeend', commentHtml);
                        const replyFormContainer = document.getElementById(`reply-to-${data.parent_id}`);
                        if (replyFormContainer) replyFormContainer.innerHTML = '';
                    }
                } else {
                    const commentsList = document.querySelector('.comments-list');
                    const emptyMsg = commentsList.querySelector('.empty-msg');
                    if (emptyMsg) emptyMsg.remove();
                    commentsList.insertAdjacentHTML('afterbegin', commentHtml);
                    form.reset();
                    
                    // Attach event listener for the new reply button
                    const newReplyBtn = document.querySelector(`.reply-trigger[data-id="${data.id}"]`);
                    if (newReplyBtn) {
                        newReplyBtn.addEventListener('click', function() {
                            const commentId = this.dataset.id;
                            const mention = this.dataset.mention ? '@' + this.dataset.mention + ' ' : '';
                            const container = document.getElementById(`reply-to-${commentId}`);
                            document.querySelectorAll('.reply-form-container').forEach(c => c.innerHTML = '');
                            const template = document.getElementById('reply-form-template').content.cloneNode(true);
                            template.querySelector('input[name="parent_id"]').value = commentId;
                            template.querySelector('.cancel-reply-btn').onclick = () => container.innerHTML = '';
                            const inputField = template.querySelector('input[name="comment_text"]');
                            if(mention) inputField.value = mention;
                            container.appendChild(template);
                            inputField.focus();
                        });
                    }
                    
                    // Update header counter
                    const headerContainer = document.querySelector('.comments-header h3');
                    if (headerContainer) {
                        const currentCountMatch = headerContainer.innerText.match(/\d+/);
                        if (currentCountMatch) {
                            const newCount = parseInt(currentCountMatch[0]) + 1;
                            headerContainer.innerText = newCount + ' ' + '<?php echo $vt['comments']; ?>';
                        }
                    }
                }
            } else {
                alert('<?php echo ($lang == "tr" ? "Yorum gönderilirken hata oluştu." : "Error posting comment."); ?>');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('<?php echo ($lang == "tr" ? "Bir hata oluştu!" : "An error occurred!"); ?>');
        })
        .finally(() => {
            submitBtn.innerText = originalText;
            submitBtn.disabled = false;
        });
    }
});

// Event delegation for reply triggers to handle dynamically added ones reliably
document.body.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('reply-trigger')) {
        <?php if(!$user_id): ?>
            window.location.href = 'auth.php';
            return;
        <?php endif; ?>
        const commentId = e.target.dataset.id;
        const mention = e.target.dataset.mention ? '@' + e.target.dataset.mention + ' ' : '';
        const container = document.getElementById(`reply-to-${commentId}`);
        if(!container) return; // safeguard
        
        document.querySelectorAll('.reply-form-container').forEach(c => c.innerHTML = '');
        const template = document.getElementById('reply-form-template').content.cloneNode(true);
        template.querySelector('input[name="parent_id"]').value = commentId;
        template.querySelector('.cancel-reply-btn').onclick = () => container.innerHTML = '';
        
        const inputField = template.querySelector('input[name="comment_text"]');
        if(mention) inputField.value = mention;
        
        container.appendChild(template);
        inputField.focus();
    }
});

document.querySelectorAll('.toggle-like-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        <?php if(!$user_id): ?>
            window.location.href = 'auth.php';
            return;
        <?php endif; ?>
        
        const action = this.dataset.action;
        const videoId = this.dataset.id;
        
        const formData = new FormData();
        formData.append('video_id', videoId);
        formData.append('action', action);
        
        fetch('api/toggle_like.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                const likeBtn = document.querySelector('.toggle-like-btn[data-action="like"]');
                const disBtn = document.querySelector('.toggle-like-btn[data-action="dislike"]');
                const likeCounter = likeBtn.querySelector('.like-counter');
                const disCounter = disBtn.querySelector('.dislike-counter');
                
                // Animasyon fonksiyonu (Yukarıya zıplama efekti)
                const animateCounter = (el, newVal) => {
                    if (el.innerText != newVal) {
                        el.style.transform = "translateY(-10px)";
                        el.style.opacity = "0";
                        setTimeout(() => {
                            el.innerText = newVal;
                            el.style.transform = "translateY(10px)";
                            requestAnimationFrame(() => {
                                el.style.transform = "translateY(0)";
                                el.style.opacity = "1";
                            });
                        }, 150);
                    }
                };

                animateCounter(likeCounter, data.likes);
                animateCounter(disCounter, data.dislikes);
                
                if (data.user_choice === 'like') {
                    likeBtn.classList.add('active-red');
                    disBtn.classList.remove('active-red');
                } else if (data.user_choice === 'dislike') {
                    disBtn.classList.add('active-red');
                    likeBtn.classList.remove('active-red');
                } else {
                    likeBtn.classList.remove('active-red');
                    disBtn.classList.remove('active-red');
                }
            }
        });
    });
});

// Comment Pagination Logic
document.addEventListener('DOMContentLoaded', () => {
    let currentLimit = 3;
    const increment = 5;
    const container = document.getElementById('comments-container');
    const showMoreBtn = document.getElementById('btn-show-more');
    const showLessBtn = document.getElementById('btn-show-less');
    
    if(!container || !showMoreBtn) return;

    const allComments = container.querySelectorAll('.comment-node');
    const totalComments = allComments.length;

    const updateVisibility = () => {
        allComments.forEach((comment, i) => {
            if (i < currentLimit) {
                comment.style.display = 'block';
            } else {
                comment.style.display = 'none';
            }
        });

        // Toggle buttons
        if (currentLimit >= totalComments) {
            showMoreBtn.style.display = 'none';
        } else {
            showMoreBtn.style.display = 'inline-block';
        }

        if (currentLimit > 3) {
            showLessBtn.style.display = 'inline-block';
        } else {
            showLessBtn.style.display = 'none';
        }
    };

    showMoreBtn.addEventListener('click', () => {
        currentLimit += increment;
        updateVisibility();
    });

    showLessBtn.addEventListener('click', () => {
        currentLimit = 3;
        updateVisibility();
        // Scroll back to comments header for better UX
        document.getElementById('comments').scrollIntoView({ behavior: 'smooth' });
    });
});
</script>
<script src="https://cdn.plyr.io/3.7.8/plyr.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const player = new Plyr('#player', {
            controls: [
                'play-large', 'restart', 'rewind', 'play', 'fast-forward', 'progress', 'current-time', 
                'duration', 'mute', 'volume', 'captions', 'settings', 'pip', 'airplay', 'fullscreen'
            ],
            seekTime: 10,
            volume: 1, // Max volume on start
            youtube: {
                noCookie: true,
                rel: 0,
                showinfo: 0,
                iv_load_policy: 3,
                modestbranding: 1
            }
        });

        // Hide volume in portrait mode to give space for progress bar
        const handleOrientation = () => {
            const isPortrait = window.innerHeight > window.innerWidth;
            const playerElement = document.querySelector('.plyr');
            if (playerElement) {
                if (isPortrait && window.innerWidth < 768) {
                    playerElement.classList.add('mobile-portrait');
                } else {
                    playerElement.classList.remove('mobile-portrait');
                }
            }
        };

        window.addEventListener('resize', handleOrientation);
        window.addEventListener('orientationchange', handleOrientation);
        handleOrientation();

        // Expose player so it can be used from the console
        window.player = player;
        
        // Check description overflow
        const contentBox = document.getElementById('video-desc');
        const wrapperBox = document.getElementById('desc-wrapper');
        const toggleBtn = document.getElementById('desc-btn');
        if(contentBox && wrapperBox && toggleBtn) {
            // Need a slight delay to ensure fonts/layout are rendered
            setTimeout(() => {
                if(contentBox.scrollHeight <= wrapperBox.clientHeight) {
                    toggleBtn.style.display = 'none';
                    document.querySelector('.expandable-desc').style.cursor = 'default';
                }
            }, 100);
        }
    });

    // Add external click to wrapper
    document.querySelector('.expandable-desc')?.addEventListener('click', function(e) {
        if(!this.classList.contains('expanded') && e.target !== document.getElementById('desc-btn')) {
            const btn = document.getElementById('desc-btn');
            if(btn && btn.style.display !== 'none') {
                btn.click();
            }
        }
    });

    function toggleDesc(e) {
        if(e) e.stopPropagation();
        const box = document.querySelector('.expandable-desc');
        const btn = document.getElementById('desc-btn');
        box.classList.toggle('expanded');
        if (box.classList.contains('expanded')) {
            btn.innerText = "<?php echo ($lang == 'tr' ? 'Daha az göster' : 'Show less'); ?>";
            box.style.cursor = 'default';
        } else {
            btn.innerText = "<?php echo ($lang == 'tr' ? '...daha fazla' : '...show more'); ?>";
            box.style.cursor = 'pointer';
        }
    }
</script>



<?php include 'includes/footer.php'; ?>
