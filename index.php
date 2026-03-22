<?php 
// Mobil tespiti (Basit regex)
$is_mobile = false;
$user_agent = $_SERVER['HTTP_USER_AGENT'];
if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $user_agent)) {
    $is_mobile = true;
}
include 'includes/header.php'; 
include 'includes/db.php'; 
?>

<div class="container" style="padding-top: 2rem;">
    <?php if($is_mobile): ?>
    <!-- Pull to Refresh Indicator -->
    <div id="pull-to-refresh" class="ptr-container">
        <div class="ptr-element">
            <i class="fas fa-sync-alt"></i>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'includes/banner_slider.php'; ?>

    <?php
    $q = isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;

    // Filters
    $f_duration = $_GET['duration'] ?? 'all';
    $f_quality = $_GET['quality'] ?? 'all';
    $f_date = $_GET['date'] ?? 'all';
    $f_sort = $_GET['sort'] ?? 'newest';
    $f_category = $_GET['category'] ?? 'all';

    $limit = 30; // Kullanıcı isteği: Sayfa başı 30 video
    $initial_limit = $is_mobile ? 10 : 30; // Mobil için başlangıçta 10, PC için 30
    $offset = ($page - 1) * $limit;

    // Base SQL
    $where_clauses = ["1=1"];
    $params = [];

    if ($f_category !== 'all') {
        $stmt_cat = $pdo->prepare("SELECT id, name_tr, name_en FROM categories WHERE slug = ?");
        $stmt_cat->execute([$f_category]);
        $active_cat = $stmt_cat->fetch();
        if ($active_cat) {
            $where_clauses[] = "category_id = ?";
            $params[] = $active_cat['id'];
        }
    }

    if ($q) {
        $where_clauses[] = "(title_tr LIKE ? OR title_en LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }

    if ($f_quality !== 'all') {
        $where_clauses[] = "quality = ?";
        $params[] = strtoupper($f_quality);
    }

    if ($f_date == 'today') {
        $where_clauses[] = "created_at >= CURDATE()";
    } elseif ($f_date == 'this_week') {
        $where_clauses[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($f_date == 'this_month') {
        $where_clauses[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }

    // Duration filter logic (assuming format like "12:34")
    if ($f_duration == 'short') {
        $where_clauses[] = "TIME_TO_SEC(CONCAT('00:', duration)) < 300"; // < 5 min
    } elseif ($f_duration == 'long') {
        $where_clauses[] = "TIME_TO_SEC(CONCAT('00:', duration)) > 1200"; // > 20 min
    }

    $where_sql = implode(" AND ", $where_clauses);
    
    // Sorting
    $order_sql = "created_at DESC";
    if ($f_sort == 'popular') $order_sql = "views DESC";
    elseif ($f_sort == 'longest') $order_sql = "TIME_TO_SEC(CONCAT('00:', duration)) DESC";

    // Count
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM videos WHERE $where_sql");
    $stmt_count->execute($params);
    $total_videos = $stmt_count->fetchColumn();

    // Data
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE $where_sql ORDER BY $order_sql LIMIT $initial_limit OFFSET $offset");
    $stmt->execute($params);
    $videos = $stmt->fetchAll();

    if ($f_category !== 'all' && isset($active_cat)) {
        $title_text = ($lang == 'tr') ? $active_cat['name_tr'] : $active_cat['name_en'];
    } else {
        $title_text = $q ? ($lang == 'tr' ? "'$q' için sonuçlar" : "Results for '$q'") : $t['popular_real'];
    }
    
    $total_pages = ceil($total_videos / $limit);
    if ($total_pages < 1) $total_pages = 1;
    ?>

    <!-- Popular Videos / Search Results -->
    <section id="videos-section" style="margin-top: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding: 0 10px; flex-wrap: wrap; gap: 1rem;">
            <h2 style="font-size: 2rem; font-weight: 800;"><?php echo $title_text; ?></h2>
            <div style="display: flex; gap: 0.8rem; align-items: center;">
                <!-- Expandable Search Bar (Mobile & Listing) -->
                <div class="expandable-search-container" id="listing-search-container">
                    <form action="index.php" method="GET" style="display: flex; width: 100%; align-items: center;">
                        <input type="text" name="q" id="listing-search-input" placeholder="<?php echo $t['search']; ?>" value="<?php echo $q; ?>" autocomplete="off">
                        <button type="button" class="search-btn" id="listing-search-toggle">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    <div id="listing-search-suggestions" class="search-suggestions"></div>
                </div>

                <button onclick="toggleFilters()" class="btn" style="background: rgba(255,255,255,0.05); color: #fff; border-radius: 50px; padding: 0.6rem 1.2rem; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-filter"></i> <?php echo ($lang == 'tr' ? 'Filtrele' : 'Filters'); ?>
                </button>
                <?php if($q) { ?>
                    <span class="badge" style="background: rgba(255,255,255,0.05); padding: 0.5rem 1rem; border-radius: 50px; font-weight: 600; font-size: 0.8rem;"><?php echo $total_videos; ?> <?php echo ($lang == 'tr' ? 'video' : 'videos'); ?></span>
                <?php } ?>
            </div>
        </div>

        <!-- Categories Slider -->
        <div class="categories-tabs-container">
            <div class="categories-tabs">
                <a href="<?php echo updateURL(['category' => 'all']); ?>" class="cat-pill <?php echo $f_category == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> <?php echo ($lang == 'tr' ? 'Tümü' : 'All'); ?>
                </a>
                <?php
                $all_cats = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
                foreach($all_cats as $c):
                    $c_name = ($lang == 'tr') ? $c['name_tr'] : $c['name_en'];
                ?>
                    <a href="<?php echo updateURL(['category' => $c['slug']]); ?>" class="cat-pill <?php echo $f_category == $c['slug'] ? 'active' : ''; ?>">
                        <?php echo $c_name; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="tabs-shadow-left"></div>
            <div class="tabs-shadow-right"></div>
        </div>

        <style>
            .categories-tabs-container {
                position: relative;
                margin-bottom: 2rem;
                padding: 0 10px;
            }
            .categories-tabs {
                display: flex;
                gap: 12px;
                overflow-x: auto;
                padding: 5px 0 15px 0;
                scrollbar-width: none;
                -ms-overflow-style: none;
                scroll-behavior: smooth;
            }
            .categories-tabs::-webkit-scrollbar { display: none; }
            .cat-pill {
                white-space: nowrap;
                background: rgba(255,255,255,0.05);
                color: rgba(255,255,255,0.7);
                padding: 0.8rem 1.8rem;
                border-radius: 50px;
                text-decoration: none;
                font-weight: 700;
                font-size: 0.9rem;
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                border: 1px solid rgba(255,255,255,0.03);
            }
            .cat-pill:hover {
                background: rgba(255,255,255,0.1);
                color: #fff;
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            }
            .cat-pill.active {
                background: var(--primary-red);
                color: #fff;
                box-shadow: 0 8px 25px rgba(211,47,47,0.4);
                border-color: rgba(255,255,255,0.1);
            }
            .tabs-shadow-right {
                position: absolute; right: 0; top: 0; height: 100%; width: 50px;
                background: linear-gradient(to left, #0f0c0c, transparent);
                pointer-events: none; opacity: 0; transition: 0.3s;
            }
            .tabs-shadow-left {
                position: absolute; left: 0; top: 0; height: 100%; width: 50px;
                background: linear-gradient(to right, #0f0c0c, transparent);
                pointer-events: none; opacity: 0; transition: 0.3s;
            }
        </style>

        <!-- Advanced Filter Bar -->
        <div id="advanced-filters" class="filter-bar <?php echo (isset($_GET['duration']) || isset($_GET['quality']) || isset($_GET['date']) || isset($_GET['sort'])) ? 'active' : ''; ?>">
            <form action="index.php" method="GET" style="display: flex; flex-wrap: wrap; gap: 1.5rem; background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);">
                <?php if($q): ?><input type="hidden" name="q" value="<?php echo $q; ?>"><?php endif; ?>
                
                <div class="filter-group">
                    <label><?php echo ($lang == 'tr' ? 'Süre' : 'Duration'); ?></label>
                    <div class="filter-options">
                        <a href="<?php echo updateURL(['duration' => 'all']); ?>" class="<?php echo $f_duration == 'all' ? 'active' : ''; ?>"><?php echo ($lang == 'tr' ? 'Tümü' : 'All'); ?></a>
                        <a href="<?php echo updateURL(['duration' => 'short']); ?>" class="<?php echo $f_duration == 'short' ? 'active' : ''; ?>"><?php echo ($lang == 'tr' ? 'Kısa (<5 dk)' : 'Short (<5m)'); ?></a>
                        <a href="<?php echo updateURL(['duration' => 'long']); ?>" class="<?php echo $f_duration == 'long' ? 'active' : ''; ?>"><?php echo ($lang == 'tr' ? 'Uzun (>20 dk)' : 'Long (>20m)'); ?></a>
                    </div>
                </div>

                <div class="filter-group">
                    <label><?php echo ($lang == 'tr' ? 'Kalite' : 'Quality'); ?></label>
                    <div class="filter-options">
                        <a href="<?php echo updateURL(['quality' => 'all']); ?>" class="<?php echo $f_quality == 'all' ? 'active' : ''; ?>">All</a>
                        <a href="<?php echo updateURL(['quality' => 'hd']); ?>" class="<?php echo $f_quality == 'HD' ? 'active' : ''; ?>">HD</a>
                        <a href="<?php echo updateURL(['quality' => '4k']); ?>" class="<?php echo $f_quality == '4K' ? 'active' : ''; ?>">4K</a>
                    </div>
                </div>

                <div class="filter-group">
                    <label><?php echo ($lang == 'tr' ? 'Yüklenme Tarihi' : 'Upload Date'); ?></label>
                    <div class="filter-options">
                        <a href="<?php echo updateURL(['date' => 'all']); ?>" class="<?php echo $f_date == 'all' ? 'active' : ''; ?>"><?php echo ($lang == 'tr' ? 'Tümü' : 'All'); ?></a>
                        <a href="<?php echo updateURL(['date' => 'today']); ?>" class="<?php echo $f_date == 'today' ? 'active' : ''; ?>"><?php echo ($lang == 'tr' ? 'Bugün' : 'Today'); ?></a>
                        <a href="<?php echo updateURL(['date' => 'this_week']); ?>" class="<?php echo $f_date == 'this_week' ? 'active' : ''; ?>"><?php echo ($lang == 'tr' ? 'Bu Hafta' : 'This Week'); ?></a>
                    </div>
                </div>

                <div class="filter-group" style="margin-left: auto;">
                    <label><?php echo ($lang == 'tr' ? 'Sıralama' : 'Sort By'); ?></label>
                    <select onchange="location = this.value;" style="background: rgba(0,0,0,0.3); color: #fff; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 5px 10px; outline: none; cursor: pointer;">
                        <option value="<?php echo updateURL(['sort' => 'newest']); ?>" <?php echo $f_sort == 'newest' ? 'selected' : ''; ?>><?php echo ($lang == 'tr' ? 'En Yeni' : 'Newest'); ?></option>
                        <option value="<?php echo updateURL(['sort' => 'popular']); ?>" <?php echo $f_sort == 'popular' ? 'selected' : ''; ?>><?php echo ($lang == 'tr' ? 'En Popüler' : 'Most Popular'); ?></option>
                        <option value="<?php echo updateURL(['sort' => 'longest']); ?>" <?php echo $f_sort == 'longest' ? 'selected' : ''; ?>><?php echo ($lang == 'tr' ? 'En Uzun' : 'Longest'); ?></option>
                    </select>
                </div>
            </form>
        </div>

        <style>
            .filter-bar { max-height: 0; overflow: hidden; transition: 0.5s cubic-bezier(0.4, 0, 0.2, 1); opacity: 0; margin-bottom: 2rem; }
            .filter-bar.active { max-height: 500px; opacity: 1; margin-bottom: 3rem; }
            .filter-group label { display: block; font-size: 0.75rem; text-transform: uppercase; font-weight: 800; opacity: 0.4; letter-spacing: 1px; margin-bottom: 0.8rem; }
            .filter-options { display: flex; gap: 8px; flex-wrap: wrap; }
            .filter-options a { text-decoration: none; color: #fff; background: rgba(255,255,255,0.05); padding: 6px 15px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; transition: 0.3s; }
            .filter-options a:hover { background: rgba(255,255,255,0.1); }
            .filter-options a.active { background: var(--primary-red); color: #fff; }
        </style>

        <script>
            function toggleFilters() {
                const bar = document.getElementById('advanced-filters');
                bar.classList.toggle('active');
            }
        </script>

        <?php 
        function updateURL($params) {
            $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $query = $_GET;
            foreach($params as $k => $v) {
                if($v == 'all') unset($query[$k]);
                else $query[$k] = $v;
            }
            return $url . (count($query) > 0 ? '?' . http_build_query($query) : '');
        }
        ?>

        <div id="video-grid-container" class="video-grid">
            <?php if (count($videos) > 0): ?>
                <?php foreach($videos as $vid): 
                    $display_title = ($lang == 'en' && !empty($vid['title_en'])) ? $vid['title_en'] : $vid['title_tr'];
                ?>
                    <a href="video.php?id=<?php echo $vid['id']; ?>" class="video-card-link" style="text-decoration: none; color: inherit;">
                        <div class="video-card animate-fade">
                            <div class="thumbnail-container" data-video-url="<?php echo $vid['video_url']; ?>" data-video-type="<?php echo $vid['video_type']; ?>">
                                <?php 
                                $thumb = $vid['thumbnail'];
                                if (!$thumb) {
                                    $thumb_src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQwIiBoZWlnaHQ9IjM2MCIgdmlld0JveD0iMCAwIDY0MCAzNjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjY0MCIgaGVpZ2h0PSIzNjAiIGZpbGw9IiMxYTFhMWEiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZmlsbD0iIzhhOGE4YSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMjQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5PcmF4PC90ZXh0Pjwvc3ZnPg==';
                                } else {
                                    $thumb_src = (strpos($thumb, 'http') === 0) ? $thumb : ltrim($thumb, '/');
                                }
                                ?>
                                <img src="<?php echo $thumb_src; ?>" alt="Thumbnail" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQwIiBoZWlnaHQ9IjM2MCIgdmlld0JveD0iMCAwIDY0MCAzNjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjY0MCIgaGVpZ2h0PSIzNjAiIGZpbGw9IiMxYTFhMWEiLz48L3N2Zz4=';">
                                
                                <?php if($vid['video_type'] == 'file' || $vid['video_type'] == 'url'): ?>
                                    <video class="preview-video" muted playsinline loop preload="none"></video>
                                <?php endif; ?>

                                <div class="duration"><?php echo !empty($vid['duration']) ? $vid['duration'] : '00:00'; ?></div>
                                <div class="play-overlay"><i class="fas fa-play"></i></div>
                                <?php if(!empty($vid['quality']) && strtoupper($vid['quality']) === '4K'): ?>
                                    <div class="quality-badge" style="position: absolute; top: 10px; left: 10px; background: var(--primary-red); color: #fff; padding: 2px 8px; border-radius: 5px; font-size: 0.7rem; font-weight: 800; z-index: 10;">4K</div>
                                <?php endif; ?>
                                <?php if(isset($vid['is_premium']) && $vid['is_premium']): ?>
                                    <div class="premium-badge" style="position: absolute; top: 10px; right: 10px; background: linear-gradient(135deg, #FFD700, #FFA500); color: #000; padding: 3px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 900; z-index: 10; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2);"><i class="fas fa-crown"></i> <?php echo ($lang == 'tr' ? 'PREMIUM' : 'PREMIUM'); ?></div>
                                <?php endif; ?>
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

        <!-- Load More Spinner (Sticky indicator for Mobile) -->
        <?php if($is_mobile): ?>
        <div id="mobile-loading" style="display: none; text-align: center; padding: 2rem;">
            <div class="spinner" style="border: 3px solid rgba(255,255,255,0.1); border-top: 3px solid var(--primary-red); border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
        </div>
        <style>@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>
        <?php endif; ?>

        <!-- Pagination UI -->
        <div class="pagination-container" id="pagination-nav">
            <?php if ($total_pages > 1): ?>
            <div class="pagination-wrapper">
                <?php 
                $q_param = $q ? "&q=$q" : "";
                if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $q_param; ?>" class="page-nav-btn"><i class="fas fa-chevron-left"></i></a>
                <?php else: ?>
                    <span class="page-nav-btn disabled"><i class="fas fa-chevron-left"></i></span>
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
                    <a href="?page=<?php echo $page + 1; ?><?php echo $q_param; ?>" class="page-nav-btn next"><i class="fas fa-chevron-right"></i></a>
                <?php else: ?>
                    <span class="page-nav-btn next disabled"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if($is_mobile): ?>
<script>
    let subPage = 1; // Başlangıçta 0 yüklendi, subPage 1 ve 2 kaldı (her biri 10 video)
    const maxSubPages = 2;
    let loading = false;
    const grid = document.getElementById('video-grid-container');
    const loader = document.getElementById('mobile-loading');
    const pagination = document.getElementById('pagination-nav');

    window.addEventListener('scroll', () => {
        if (subPage > maxSubPages) return;
        if (loading) return;

        // Sayfa sonuna yaklaştık mı kontrolü
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 800) {
            loadNextVideos();
        }
    });

    async function loadNextVideos() {
        loading = true;
        loader.style.display = 'block';
        
        try {
            const response = await fetch(`api/load-videos.php?page=<?php echo $page; ?>&sub_page=${subPage}&q=<?php echo urlencode($q); ?>`);
            const html = await response.text();
            
            if (html.trim() !== "") {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                const newCards = tempDiv.querySelectorAll('.video-card-link');
                
                newCards.forEach(card => {
                    grid.appendChild(card);
                });
                
                subPage++;
            } else {
                subPage = 99; // Bitti
            }
        } catch (e) {
            console.error(e);
        } finally {
            loading = false;
            loader.style.display = 'none';
        }
    }

    // Pull to Refresh Implementation
    let startY = 0;
    let dist = 0;
    const ptr = document.getElementById('pull-to-refresh');
    const threshold = 100;

    window.addEventListener('touchstart', (e) => {
        if (window.scrollY <= 2) {
            startY = e.touches[0].pageY;
            ptr.style.transition = 'none';
        }
    }, {passive: true});

    window.addEventListener('touchmove', (e) => {
        if (window.scrollY <= 2) {
            const currentY = e.touches[0].pageY;
            dist = currentY - startY;

            if (dist > 30) {
                ptr.classList.add('ptr-active');
                // Resistance pull: moves 0 to 120px
                const pull = Math.min(dist * 0.4, 120); 
                ptr.style.transform = `translateY(${pull}px)`;
                
                const icon = ptr.querySelector('i');
                icon.style.transform = `rotate(${dist * 1.5}deg)`;
            }
        }
    }, {passive: true});

    window.addEventListener('touchend', () => {
        if (dist >= threshold) {
            ptr.classList.add('ptr-refreshing');
            ptr.style.transition = 'transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
            ptr.style.transform = `translateY(100px)`;
            
            if ("vibrate" in navigator) navigator.vibrate(15);
            setTimeout(() => window.location.reload(), 800);
        } else {
            ptr.style.transition = 'transform 0.3s ease, opacity 0.3s';
            ptr.style.transform = `translateY(-100%)`;
            setTimeout(() => ptr.classList.remove('ptr-active'), 300);
        }
        dist = 0;
    });
</script>
<?php endif; ?>


<script>
    // Expandable Search Logic
    const searchContainer = document.getElementById('listing-search-container');
    const searchToggle = document.getElementById('listing-search-toggle');
    const searchInput = document.getElementById('listing-search-input');
    const suggestionsBox = document.getElementById('listing-search-suggestions');

    searchToggle.addEventListener('click', (e) => {
        if (!searchContainer.classList.contains('active')) {
            searchContainer.classList.add('active');
            searchInput.focus();
        } else {
            if (searchInput.value.trim() !== '') {
                searchContainer.querySelector('form').submit();
            } else {
                searchContainer.classList.remove('active');
            }
        }
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (searchContainer && !searchContainer.contains(e.target)) {
            if (searchInput.value.trim() === '') {
                searchContainer.classList.remove('active');
                suggestionsBox.style.display = 'none';
            }
        }
    });

    // Suggestions for Listing Search
    searchInput.addEventListener('input', async (e) => {
        const q = e.target.value.trim();
        if (q.length < 2) {
            suggestionsBox.style.display = 'none';
            return;
        }

        try {
            const response = await fetch(`api/search-suggestions.php?q=${encodeURIComponent(q)}`);
            const suggestions = await response.json();

            if (suggestions.length > 0) {
                suggestionsBox.innerHTML = suggestions.map(s => `
                    <div class="suggestion-item" onclick="location.href='index.php?q=${encodeURIComponent(s)}'">
                        <i class="fas fa-search" style="margin-right:8px; opacity:0.4;"></i> ${s}
                    </div>
                `).join('');
                suggestionsBox.style.display = 'block';
            } else {
                suggestionsBox.style.display = 'none';
            }
        } catch (error) {
            console.error('Suggestions error:', error);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
