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
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_balance = $stmt->fetchColumn() ?: "0.00";
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
        'watch_now' => 'Şimdi İzle'
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
        'watch_now' => 'Watch Now'
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
    </style>
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
            <form action="index.php" method="GET" class="search-bar">
                <input type="text" name="q" placeholder="<?php echo $t['search']; ?>" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="nav-right">
            <button id="theme-toggle" class="btn-icon">
                <i class="fas fa-moon"></i>
            </button>
            <div class="header-actions">
                <div class="lang-pill smooth-transition">
                    <a href="?lang=tr" class="<?php echo $lang == 'tr' ? 'active' : ''; ?>">TR</a>
                    <a href="?lang=en" class="<?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
                </div>
                <?php if(isset($_SESSION['username'])): ?>
                    <a href="profile.php" class="user-pill" style="text-decoration: none; color: inherit;">
                        <div class="balance-pill" title="Mevcut Bakiyeniz">
                            <i class="fas fa-wallet" style="color: var(--primary-red); font-size: 0.8rem;"></i>
                            <span><?php echo number_format($user_balance, 2); ?> AZN</span>
                        </div>
                        <div class="user-avatar-custom">
                            <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                        </div>
                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </a>
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
