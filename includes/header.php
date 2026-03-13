<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Dil seçimi
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'tr'; // Default Turkish as requested
}
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'] == 'tr' ? 'tr' : 'en';
    // URL'yi temizlemek için yönlendirme yapalım
    $url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $url");
    exit;
}
$lang = $_SESSION['lang'];

// Global DB bağlantısı (Tüm frontend ayarları ve statlar için)
include_once __DIR__ . '/db.php';

// Site Ayarlarını Çek
$stmt = $pdo->query("SELECT * FROM settings");
$site_settings = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $site_settings[$r['setting_key']] = $r['setting_value'];
}
// Varsayılan değerler
$s_title = !empty($site_settings['site_title']) ? htmlspecialchars($site_settings['site_title']) : 'Orax Premium';
$s_desc = htmlspecialchars($site_settings['seo_desc'] ?? '');
$s_keyw = htmlspecialchars($site_settings['seo_keywords'] ?? '');
$s_fav = htmlspecialchars($site_settings['favicon'] ?? '');
$s_logo = htmlspecialchars($site_settings['logo'] ?? '');
$s_css = htmlspecialchars($site_settings['custom_css'] ?? '');
$s_color = htmlspecialchars($site_settings['primary_color'] ?? '#D32F2F');
$s_tagline = htmlspecialchars($site_settings['site_tagline'] ?? 'Premium HD Adult Videos');
$s_logo_width = htmlspecialchars($site_settings['logo_width'] ?? '150px');

// Kullanıcı bilgilerini ve bakiyesini çek
$user_balance = "0.00";
$is_vip = false;
$vip_expire = null;
$vip_no_ads = false;
$vip_premium_access = false;

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT balance, is_vip, vip_expire, vip_no_ads, vip_premium_access FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u_data = $stmt->fetch();
    
    if ($u_data) {
        $user_balance = $u_data['balance'] ?: "0.00";
        $is_vip = (bool)$u_data['is_vip'];
        $vip_expire = $u_data['vip_expire'];
        $vip_no_ads = (bool)$u_data['vip_no_ads'];
        $vip_premium_access = (bool)$u_data['vip_premium_access'];

        // VIP Süre Kontrolü
        if ($is_vip && $vip_expire && strtotime($vip_expire) < time()) {
            $pdo->prepare("UPDATE users SET is_vip = 0, vip_no_ads = 0, vip_premium_access = 0 WHERE id = ?")->execute([$_SESSION['user_id']]);
            $is_vip = false;
            $vip_no_ads = false;
            $vip_premium_access = false;
        }
    }
}

// Reklamları Çek (REKLAMSIZ VIP DEĞİLSE)
$ads = [];
if (!$vip_no_ads) {
    $stmt_ads = $pdo->query("SELECT * FROM ads WHERE status = 1");
    while ($ad_row = $stmt_ads->fetch(PDO::FETCH_ASSOC)) {
        $ads[$ad_row['ad_key']] = $ad_row;
    }
}

// Yerelleştirme dizisi
$texts = [
    'tr' => [
        'home' => 'Anasayfa',
        'categories' => 'Kategoriler',
        'login' => 'Giriş Yap',
        'search' => 'Ara...',
        'popular' => 'Popüler',
        'popular_real' => 'Popüler Videolar',
        'new' => 'Yeni Eklenenler',
        'trending' => 'Trendler',
        'subscriptions' => 'Abonelikler',
        'library' => 'Kitaplık',
        'footer_text' => '© 2026 Orax Premium. Tüm hakları saklıdır.',
        'watch_now' => 'Şimdi İzle',
        'get_premium' => 'VIP Üye Ol',
        'premium' => 'Premium',
        'insufficient_balance' => 'Yetersiz bakiye'
    ],
    'en' => [
        'home' => 'Home',
        'categories' => 'Categories',
        'login' => 'Login',
        'search' => 'Search...',
        'popular' => 'Popular',
        'popular_real' => 'Popular Videos',
        'new' => 'New Arrivals',
        'trending' => 'Trending',
        'subscriptions' => 'Subscriptions',
        'library' => 'Library',
        'footer_text' => '© 2026 Orax Premium. All rights reserved.',
        'watch_now' => 'Watch Now',
        'get_premium' => 'Get Premium',
        'premium' => 'Premium',
        'insufficient_balance' => 'Insufficient balance'
    ]
];

$t = $texts[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $s_title; ?></title>
    <?php if($s_desc): ?><meta name="description" content="<?php echo $s_desc; ?>"><?php endif; ?>
    <?php if($s_keyw): ?><meta name="keywords" content="<?php echo $s_keyw; ?>"><?php endif; ?>
    <?php if($s_fav && file_exists(__DIR__ . '/../' . $s_fav)): ?><link rel="icon" href="<?php echo $s_fav; ?>"><?php endif; ?>
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
    <style>
        :root { 
            --plyr-color-main: <?php echo $s_color; ?>;
            --primary-red: <?php echo $s_color; ?>;
            --accent-red: <?php echo $s_color; ?>; 
        }
        <?php if($s_css) echo $s_css; ?>
        .ad-banner-top { width: 100%; display: flex; justify-content: center; margin-bottom: 2rem; overflow: hidden; border-radius: 15px; }
        .ad-banner-top img { max-width: 100%; height: auto; border-radius: 15px; }
    </style>
    <!-- Pop-under Ad -->
    <?php if(isset($ads['pop_under']) && $ads['pop_under']['ad_type'] == 'code'): ?>
        <?php echo $ads['pop_under']['content']; ?>
    <?php endif; ?>
</head>
<body class="dark-mode sidebar-layout">
    <!-- Top Navbar -->
    <nav class="top-nav">
        <div class="nav-left">
            <button id="sidebar-toggle" class="btn-icon"><i class="fas fa-bars"></i></button>
            <a href="index.php" class="logo-link" style="display:flex; align-items:center;">
                <?php if($s_logo && file_exists(__DIR__ . '/../' . $s_logo)): ?>
                    <img src="<?php echo $s_logo; ?>" alt="Site Logo" style="width: <?php echo $s_logo_width; ?>; max-height: 50px; object-fit: contain;">
                <?php else: ?>
                    <div class="logo"><?php echo explode(' ', $s_title)[0]; ?></div>
                <?php endif; ?>
            </a>
        </div>
        <div class="nav-center">
            <form action="index.php" method="GET" class="search-bar" id="top-search-form" autocomplete="off">
                <button type="button" id="close-mobile-search" class="btn-icon mobile-only" style="display:none; margin-right: 10px; border:none; background:none; color:white;"><i class="fas fa-times"></i></button>
                <input type="text" name="q" id="search-input" placeholder="<?php echo $t['search']; ?>" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
                <div id="search-suggestions" class="search-suggestions-container"></div>
            </form>
        </div>

        <style>
            .search-bar { position: relative; }
            .search-suggestions-container {
                position: absolute; top: calc(100% + 5px); left: 0; width: 100%;
                background: #1a1a1a; border: 1px solid rgba(255,255,255,0.05);
                border-radius: 15px; overflow: hidden; display: none; z-index: 2000;
                box-shadow: 0 20px 40px rgba(0,0,0,0.5); backdrop-filter: blur(20px);
            }
            .suggestion-item {
                padding: 12px 20px; cursor: pointer; display: flex; align-items: center; gap: 12px;
                color: rgba(255,255,255,0.7); font-size: 0.9rem; transition: 0.2s; border-bottom: 1px solid rgba(255,255,255,0.02);
            }
            .suggestion-item:last-child { border-bottom: none; }
            .suggestion-item:hover { background: rgba(255,255,255,0.03); color: #fff; }
            .suggestion-item i { font-size: 0.8rem; opacity: 0.4; }

            @media (max-width: 768px) {
                .mobile-only { display: none !important; } /* Silindi veya Gizlendi */
                .top-nav .nav-center { display: none !important; }
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const nav = document.querySelector('.top-nav');
                const openSearch = document.getElementById('open-mobile-search');
                const closeSearch = document.getElementById('close-mobile-search');
                const searchInput = document.getElementById('search-input');
                const suggestionsBox = document.getElementById('search-suggestions');

                if (openSearch) {
                    openSearch.onclick = () => {
                        nav.classList.add('search-mode');
                        setTimeout(() => searchInput.focus(), 300);
                    };
                }
                if (closeSearch) {
                    closeSearch.onclick = () => {
                        nav.classList.remove('search-mode');
                        suggestionsBox.style.display = 'none';
                    };
                }

                searchInput.addEventListener('input', async (e) => {
                    const q = e.target.value.trim();
                    if (q.length < 2) {
                        suggestionsBox.style.display = 'none';
                        return;
                    }

                    try {
                        const res = await fetch(`api/search-suggestions.php?q=${encodeURIComponent(q)}`);
                        const suggestions = await res.json();

                        if (suggestions.length > 0) {
                            suggestionsBox.innerHTML = suggestions.map(s => `
                                <div class="suggestion-item" onclick="window.location.href='index.php?q=${encodeURIComponent(s.title)}'">
                                    <i class="fas fa-search"></i>
                                    <span>${s.title}</span>
                                </div>
                            `).join('');
                            suggestionsBox.style.display = 'block';
                        } else {
                            suggestionsBox.style.display = 'none';
                        }
                    } catch (e) { console.error(e); }
                });

                document.addEventListener('click', (e) => {
                    if (!document.getElementById('top-search-form').contains(e.target)) {
                        suggestionsBox.style.display = 'none';
                    }
                });
            });
        </script>
        <div class="nav-right">
            <button id="open-mobile-search" class="btn-icon mobile-only" style="display:none; margin-right: 10px;"><i class="fas fa-search"></i></button>
            <button id="theme-toggle" class="btn-icon">
                <i class="fas fa-moon"></i>
            </button>
            <div class="header-actions">
                <div class="lang-pill smooth-transition">
                    <a href="?lang=tr" class="<?php echo $lang == 'tr' ? 'active' : ''; ?>">TR</a>
                    <a href="?lang=en" class="<?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
                </div>
                <?php if(isset($_SESSION['username'])): ?>
                    <div class="user-pill-container" style="display: flex; align-items: center; gap: 10px;">
                        <a href="profile.php" class="user-pill">
                            <div class="balance-pill desktop-only" title="Mevcut Bakiyeniz" onclick="event.preventDefault(); location.href='deposit.php'">
                                <i class="fas fa-wallet" style="color: var(--primary-red); font-size: 0.8rem;"></i>
                                <span><?php echo number_format($user_balance, 2); ?> AZN</span>
                                <i class="fas fa-plus-circle" style="opacity: 0.6; font-size: 0.7rem;"></i>
                            </div>
                            <div class="user-avatar-custom" style="position: relative; <?php echo $is_vip ? 'border: 2px solid gold; box-shadow: 0 0 10px gold;' : ''; ?>">
                                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                                <?php if($is_vip): ?><div style="position: absolute; bottom: -5px; right: -5px; background: gold; color: black; font-size: 10px; padding: 2px 4px; border-radius: 4px; font-weight: 900;"><i class="fas fa-gem"></i></div><?php endif; ?>
                            </div>
                            <div class="user-info-text desktop-only" style="display: flex; flex-direction: column; line-height: 1.1;">
                                <span style="font-weight: 800; font-size: 0.9rem; <?php echo $is_vip ? 'color: gold;' : ''; ?>"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <?php if(!$is_vip): ?>
                                    <span style="font-size: 0.65rem; color: #FFD700; font-weight: 950; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9;">VIP OL <i class="fas fa-chevron-right" style="font-size: 0.5rem;"></i></span>
                                <?php else: ?>
                                    <span style="font-size: 0.65rem; color: #4CAF50; font-weight: 950; text-transform: uppercase;">Premium</span>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="auth.php" class="btn btn-primary" style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; padding: 0;" title="<?php echo $t['login']; ?>">
                        <i class="fas fa-sign-in-alt"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Sidebar Wrapper -->
    <div class="app-container">
        <aside class="sidebar">
            <ul class="sidebar-menu">
                <?php if(isset($_SESSION['user_id'])): ?>
                <div class="sidebar-user-header" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); background: linear-gradient(to bottom, rgba(211,47,47,0.05), transparent);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="user-avatar-custom" style="width: 45px; height: 45px; font-size: 1.2rem; <?php echo $is_vip ? 'border: 2px solid gold; box-shadow: 0 0 10px gold;' : ''; ?>">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-weight: 800; font-size: 1.1rem; <?php echo $is_vip ? 'color: gold;' : ''; ?>"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <?php if($is_vip): ?>
                                <span style="font-size: 0.7rem; color: #4CAF50; font-weight: 900; text-transform: uppercase;"><i class="fas fa-gem"></i> Premium Üye</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mobile-sidebar-balance" style="background: rgba(0,0,0,0.3); border-radius: 15px; padding: 1rem; display: flex; justify-content: space-between; align-items: center;">
                        <div class="balance-info">
                            <span class="balance-label" style="font-size: 0.7rem; opacity: 0.5; text-transform: uppercase; font-weight: 800;"><?php echo ($lang == 'tr' ? 'Bakiyem' : 'My Balance'); ?></span>
                            <span class="balance-amount" style="font-size: 1.1rem; font-weight: 900; color: #fff; display: block;"><?php echo number_format($user_balance, 2); ?> AZN</span>
                        </div>
                        <button class="add-balance-btn" onclick="location.href='deposit.php'" style="background: var(--primary-red); color: white; border: none; width: 35px; height: 35px; border-radius: 10px; cursor: pointer;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <?php if(!$is_vip): ?>
                        <a href="premium.php" class="btn btn-vip-mobile" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: #000; text-decoration: none; padding: 0.8rem; border-radius: 12px; font-weight: 900; text-align: center; display: block; font-size: 0.9rem; box-shadow: 0 5px 15px rgba(255,215,0,0.2);">
                            <i class="fas fa-gem"></i> <?php echo ($lang == 'tr' ? 'VIP OL' : 'BECOME VIP'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php $curr = basename($_SERVER['PHP_SELF']); ?>
                <li><a href="index.php" class="<?php echo $curr == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> <span><?php echo $t['home']; ?></span></a></li>
                <li><a href="categories.php" class="<?php echo ($curr == 'categories.php' || $curr == 'category.php') ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> <span><?php echo $t['categories']; ?></span></a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <hr>
                    <li class="menu-header" style="padding: 0.8rem 1.5rem; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="opacity: 0.4;"><?php echo ($lang == 'tr' ? 'Kitaplığım' : 'My Library'); ?></span>
                        <button onclick="openPlaylistDialog()" class="btn-icon" style="background: var(--primary-red); color: white; font-size: 0.85rem; width: 26px; height: 26px; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(211,47,47,0.5); border: none; cursor: pointer; transition: 0.3s;" onmouseover="this.style.background='#ff3d3d'; this.style.transform='scale(1.1)'" onmouseout="this.style.background='var(--primary-red)'; this.style.transform='scale(1)'">
                            <i class="fas fa-plus"></i>
                        </button>
                    </li>

                    <script>
                        function openPlaylistDialog() {
                            const overlay = document.getElementById('playlist-dialog-overlay');
                            const dialog = document.getElementById('playlist-dialog');
                            overlay.style.display = 'flex';
                            setTimeout(() => dialog.classList.add('active'), 10);
                        }
                        function closePlaylistDialog() {
                            const overlay = document.getElementById('playlist-dialog-overlay');
                            const dialog = document.getElementById('playlist-dialog');
                            dialog.classList.remove('active');
                            setTimeout(() => overlay.style.display = 'none', 300);
                        }
                    </script>

                    <?php
                    $side_playlists = $pdo->prepare("SELECT p.*, (SELECT COUNT(*) FROM playlist_videos WHERE playlist_id = p.id) as v_count FROM playlists p WHERE p.user_id = ? LIMIT 10");
                    $side_playlists->execute([$_SESSION['user_id']]);
                    $curr_pl_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                    foreach($side_playlists->fetchAll() as $spl): ?>
                        <li>
                            <a href="playlist.php?id=<?php echo $spl['id']; ?>" class="<?php echo ($curr == 'playlist.php' && $curr_pl_id == $spl['id']) ? 'active' : ''; ?>" style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-list-ul"></i> 
                                    <span><?php echo htmlspecialchars($spl['name']); ?></span>
                                </div>
                                <span style="font-size: 0.7rem; opacity: 0.4; font-weight: bold; background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px;"><?php echo $spl['v_count']; ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Sidebar Ad Slot -->
                <?php if(isset($ads['sidebar_banner'])): ?>
                    <div class="sidebar-ad-container" style="margin: 2rem 1rem; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.2);">
                        <?php if($ads['sidebar_banner']['ad_type'] == 'code'): ?>
                            <?php echo $ads['sidebar_banner']['content']; ?>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($ads['sidebar_banner']['link_url']); ?>" target="_blank">
                                <img src="<?php echo $ads['sidebar_banner']['content']; ?>" alt="Sidebar Ad" style="width: 100%; height: auto; display: block;">
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </ul>
        </aside>
        
    <!-- Playlist Creation Dialog -->
    <div class="orax-dialog-overlay" id="playlist-dialog-overlay" style="z-index: 9999;">
        <div class="orax-dialog" id="playlist-dialog">
            <div class="dialog-header">
                <i class="fas fa-list-ul"></i>
                <span style="color: white;"><?php echo ($lang == 'tr' ? 'Yeni Oynatma Listesi' : 'New Playlist'); ?></span>
            </div>
            <div class="dialog-body">
                <form action="video.php" method="POST" id="sidebar-create-playlist-form">
                    <p style="margin-bottom: 1rem; font-size: 0.9rem; color: rgba(255,255,255,0.6);"><?php echo ($lang == 'tr' ? 'Listeniz için bir isim girin:' : 'Enter a name for your list:'); ?></p>
                    <input type="text" name="playlist_name" placeholder="<?php echo ($lang == 'tr' ? 'Örn: Favorilerim' : 'e.g. My Favorites'); ?>" required 
                           style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); padding: 0.8rem; border-radius: 12px; color: white; outline: none; transition: 0.3s;"
                           onfocus="this.style.borderColor='var(--primary-red)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'">
                </form>
            </div>
            <div class="dialog-footer">
                <button class="btn-dialog btn-dialog-cancel" onclick="closePlaylistDialog()"><?php echo ($lang == 'tr' ? 'İptal' : 'Cancel'); ?></button>
                <button class="btn-dialog btn-dialog-confirm" onclick="document.getElementById('sidebar-create-playlist-form').submit()"><?php echo ($lang == 'tr' ? 'Oluştur' : 'Create'); ?></button>
            </div>
        </div>
    </div>

    <main class="main-content-area">
        <!-- Header Banner Ad -->
        <?php if(isset($ads['header_banner'])): ?>
            <div class="ad-banner-top container">
                <?php if($ads['header_banner']['ad_type'] == 'code'): ?>
                    <?php echo $ads['header_banner']['content']; ?>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($ads['header_banner']['link_url']); ?>" target="_blank">
                        <img src="<?php echo $ads['header_banner']['content']; ?>" alt="Ad">
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
