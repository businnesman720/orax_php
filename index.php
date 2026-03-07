<?php include 'includes/header.php'; ?>
<?php include 'includes/db.php'; ?>

<div class="container" style="padding-top: 2rem;">
    <?php
    $q = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $limit = 24;
    $offset = ($page - 1) * $limit;

    // Real DB Query for Search or All
    if ($q) {
        $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM videos WHERE title_tr LIKE ? OR title_en LIKE ?");
        $stmt_count->execute(["%$q%", "%$q%"]);
        $total_videos = $stmt_count->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM videos WHERE title_tr LIKE ? OR title_en LIKE ? LIMIT $limit OFFSET $offset");
        $stmt->execute(["%$q%", "%$q%"]);
        $videos = $stmt->fetchAll();
        $title_text = ($lang == 'tr' ? "'$q' için sonuçlar" : "Results for '$q'");
    } else {
        $total_videos = $pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn();
        // POPULAR LOGIC: Sort by views DESC, then newest DESC as fallback
        $stmt = $pdo->query("SELECT *, (views) as popularity FROM videos ORDER BY popularity DESC, created_at DESC LIMIT $limit OFFSET $offset");
        $videos = $stmt->fetchAll();
        $title_text = $t['popular_real'];
    }

    $total_pages = ceil($total_videos / $limit);
    if ($total_pages < 1) $total_pages = 1;
    ?>

    <!-- Popular Videos / Search Results -->
    <section id="videos-section" style="margin-top: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="font-size: 2rem;"><?php echo $title_text; ?></h2>
            <?php if($q): ?>
                <span style="opacity: 0.5;"><?php echo $total_videos; ?> <?php echo ($lang == 'tr' ? 'video bulundu' : 'videos found'); ?></span>
            <?php endif; ?>
        </div>
        
        <div id="video-container" class="video-grid">
            <?php if (count($videos) > 0): ?>
                <?php foreach($videos as $vid): 
                    $display_title = ($lang == 'en' && !empty($vid['title_en'])) ? $vid['title_en'] : $vid['title_tr'];
                ?>
                    <a href="video.php?id=<?php echo $vid['id']; ?>" class="video-card-link" style="text-decoration: none; color: inherit;">
                        <div class="video-card animate-fade">
                            <div class="thumbnail-container">
                                <?php 
                                $thumb = $vid['thumbnail'];
                                if (!$thumb) {
                                    $thumb_src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQwIiBoZWlnaHQ9IjM2MCIgdmlld0JveD0iMCAwIDY0MCAzNjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjY0MCIgaGVpZ2h0PSIzNjAiIGZpbGw9IiMxYTFhMWEiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZmlsbD0iIzhhOGE4YSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMjQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5PcmF4PC90ZXh0Pjwvc3ZnPg==';
                                } else {
                                    $thumb_src = (strpos($thumb, 'http') === 0) ? $thumb : ltrim($thumb, '/');
                                }
                                ?>
                                <img src="<?php echo $thumb_src; ?>" alt="Thumbnail" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQwIiBoZWlnaHQ9IjM2MCIgdmlld0JveD0iMCAwIDY0MCAzNjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjY0MCIgaGVpZ2h0PSIzNjAiIGZpbGw9IiMxYTFhMWEiLz48L3N2Zz4=';">

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
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 5rem; opacity: 0.5;">
                    <i class="fas fa-film" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <p><?php echo ($lang == 'tr' ? 'Henüz video eklenmemiş.' : 'No videos found.'); ?></p>
                    <?php if($q): ?>
                        <a href="index.php" class="btn btn-primary" style="margin-top: 1rem; border-radius: 50px;">Tüm Videoları Gör</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination UI -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination-wrapper">
                <?php 
                $q_param = $q ? "&q=$q" : "";
                if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $q_param; ?>" class="page-nav-btn">Prev</a>
                <?php else: ?>
                    <span class="page-nav-btn disabled">Prev</span>
                <?php endif; ?>

                <?php 
                $range = 2;
                if ($page > $range + 1) {
                    echo '<a href="?page=1'.$q_param.'" class="page-num-btn">1</a>';
                    if ($page > $range + 2) echo '<span class="page-dots">...</span>';
                }

                for ($p = max(1, $page - $range); $p <= min($total_pages, $page + $range); $p++): ?>
                    <a href="?page=<?php echo $p; ?><?php echo $q_param; ?>" class="page-num-btn <?php echo ($p == $page) ? 'active' : ''; ?>">
                        <?php echo $p; ?>
                    </a>
                <?php endfor; ?>

                <?php 
                if ($page < $total_pages - $range) {
                    if ($page < $total_pages - $range - 1) echo '<span class="page-dots">...</span>';
                    echo '<a href="?page='.$total_pages.$q_param.'" class="page-num-btn">'.$total_pages.'</a>';
                }
                ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $q_param; ?>" class="page-nav-btn next">Next</a>
                <?php else: ?>
                    <span class="page-nav-btn next disabled">Next</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
