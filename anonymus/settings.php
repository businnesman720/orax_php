<?php
include 'auth.php';
include '../includes/db.php';

if (!isset($_SESSION['admin_lang'])) {
    $_SESSION['admin_lang'] = 'en';
}
if (isset($_GET['lang'])) {
    $_SESSION['admin_lang'] = $_GET['lang'] == 'tr' ? 'tr' : 'en';
    $clean_url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $clean_url");
    exit;
}
$lang = $_SESSION['admin_lang'];

$texts = [
    'en' => [
        'title' => 'Admin Settings',
        'dashboard' => 'Dashboard',
        'videos' => 'Videos',
        'categories' => 'Categories',
        'users' => 'Users',
        'settings' => 'Site Settings',
        'logout' => 'Safe Logout',
        'view_site' => 'Live View',
        'tab_general' => 'General',
        'tab_seo' => 'SEO Settings',
        'tab_theme' => 'Brand & Theme',
        'site_title' => 'Site Title',
        'seo_desc' => 'Meta Description',
        'seo_keywords' => 'Meta Keywords',
        'custom_css' => 'Custom CSS',
        'favicon' => 'Favicon Image',
        'logo' => 'Logo Image',
        'logo_width' => 'Logo Width (Frontend Only)',
        'admin_logo_width' => 'Admin Logo Width',
        'site_tagline' => 'Site Tagline',
        'contact_email' => 'Contact Email',
        'age_warning' => 'Enable 18+ Age Verification Modal',
        'google_analytics' => 'Google Analytics Tracking ID',
        'primary_color' => 'Primary Brand Color',
        'save' => 'Save Settings',
        'saved' => 'Settings saved successfully.'
    ],
    'tr' => [
        'title' => 'Site Ayarları',
        'dashboard' => 'Panel',
        'videos' => 'Videolar',
        'categories' => 'Kategoriler',
        'users' => 'Kullanıcılar',
        'settings' => 'Site Ayarları',
        'logout' => 'Güvenli Çıkış',
        'view_site' => 'Siteyi Gör',
        'tab_general' => 'Genel',
        'tab_seo' => 'SEO Ayarları',
        'tab_theme' => 'Marka & Tema',
        'site_title' => 'Site Başlığı',
        'seo_desc' => 'Meta Açıklama',
        'seo_keywords' => 'Meta Anahtar Kelimeler',
        'custom_css' => 'Özel CSS Kodları',
        'favicon' => 'Site İkonu (Favicon)',
        'logo' => 'Site Logosu',
        'logo_width' => 'Logo Genişliği (Sadece Ön Yüzde)',
        'admin_logo_width' => 'Admin Logo Genişliği (Panel İçin)',
        'site_tagline' => 'Site Sloganı',
        'contact_email' => 'İletişim E-Postası',
        'age_warning' => '18+ Yaş Doğrulama Uyarısını Aktifleştir',
        'google_analytics' => 'Google Analytics ID (veya Head Kodu)',
        'primary_color' => 'Ana Marka Rengi',
        'save' => 'Ayarları Kaydet',
        'saved' => 'Ayarlar başarıyla kaydedildi.'
    ]
];
$t = $texts[$lang];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_settings') {
    $updates = [
        'site_title' => trim($_POST['site_title']),
        'site_tagline' => trim($_POST['site_tagline']),
        'contact_email' => trim($_POST['contact_email']),
        'age_warning' => isset($_POST['age_warning']) ? '1' : '0',
        'seo_desc' => trim($_POST['seo_desc']),
        'seo_keywords' => trim($_POST['seo_keywords']),
        'google_analytics' => trim($_POST['google_analytics']),
        'custom_css' => trim($_POST['custom_css']),
        'primary_color' => trim($_POST['primary_color']),
        'logo_width' => trim($_POST['logo_width']),
        'admin_logo_width' => trim($_POST['admin_logo_width'])
    ];

    // File uploads
    if (!empty($_FILES['favicon']['name'])) {
        $favicon = 'uploads/settings/' . uniqid() . '_' . basename($_FILES['favicon']['name']);
        if (!is_dir('../uploads/settings/')) mkdir('../uploads/settings/', 0777, true);
        if (move_uploaded_file($_FILES['favicon']['tmp_name'], '../' . $favicon)) {
            $updates['favicon'] = $favicon;
        }
    }
    if (!empty($_FILES['logo']['name'])) {
        $logo = 'uploads/settings/' . uniqid() . '_' . basename($_FILES['logo']['name']);
        if (!is_dir('../uploads/settings/')) mkdir('../uploads/settings/', 0777, true);
        if (move_uploaded_file($_FILES['logo']['tmp_name'], '../' . $logo)) {
            $updates['logo'] = $logo;
        }
    }

    foreach ($updates as $key => $val) {
        $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $val]);
    }
    header("Location: settings.php?msg=success");
    exit;
}

// Get current settings
$stmt = $pdo->query("SELECT * FROM settings");
$current_settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORAX - <?php echo $t['title']; ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --admin-sidebar: #151515;
            --admin-content: #1e1e1e;
        }

        body {
            background-color: var(--admin-content);
            margin: 0; padding: 0;
            display: flex; height: 100vh;
            overflow: hidden;
            font-family: 'Outfit', sans-serif;
            color: white;
        }

        /* Sidebar matching dashboard */
        .sidebar {
            width: 300px;
            background: var(--admin-sidebar);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            display: flex; flex-direction: column;
            padding: 2.5rem;
            transition: 0.4s;
            z-index: 100;
        }
        .sidebar-logo {
            font-size: 2.5rem; font-weight: 950; color: var(--primary-red);
            letter-spacing: 5px; margin-bottom: 4rem; text-shadow: 0 0 20px rgba(211, 47, 47, 0.3);
            text-align: center;
        }
        .side-nav { list-style: none; padding: 0; }
        .side-nav li { margin-bottom: 1rem; }
        .side-nav a {
            display: flex; align-items: center; gap: 1.2rem; padding: 1.2rem 1.5rem;
            text-decoration: none; color: white; opacity: 0.6; border-radius: 15px;
            font-weight: 600; transition: 0.4s; border: 1px solid transparent;
        }
        .side-nav a:hover, .side-nav a.active {
            opacity: 1; background: rgba(211, 47, 47, 0.08);
            color: var(--primary-red); border-color: rgba(211, 47, 47, 0.2);
            transform: translateX(5px);
        }

        .lang-pills-admin { display: flex; gap: 0.5rem; background: rgba(0,0,0,0.3); padding: 0.3rem; border-radius: 50px; }
        .lang-pills-admin a { text-decoration: none; color: white; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; opacity: 0.4; }
        .lang-pills-admin a.active { background: var(--primary-red); opacity: 1; }

        .main-pane {
            flex: 1; padding: 3rem; overflow-y: auto;
            background: radial-gradient(circle at top right, rgba(211, 47, 47, 0.03) 0%, rgba(30, 30, 30, 1) 50%);
        }

        .header-bar {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;
        }
        .header-bar h1 { font-size: 2.5rem; font-weight: 800; }

        /* Tabs styling */
        .tabs { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.5rem; }
        .tab {
            padding: 1rem 2rem; cursor: pointer; border-radius: 15px; color: white; opacity: 0.5;
            font-weight: 700; transition: 0.3s;
        }
        .tab:hover { opacity: 0.8; background: rgba(255,255,255,0.02); }
        .tab.active { opacity: 1; background: var(--primary-red); color: white; }

        .tab-content { display: none; animation: fadeIn 0.4s ease; }
        .tab-content.active { display: block; }

        @keyframes fadeIn { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform: translateY(0); } }

        .settings-card {
            background: rgba(255, 255, 255, 0.02);
            padding: 2.5rem; border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 2rem;
        }

        .input-group { margin-bottom: 1.8rem; }
        .input-group label { display: block; margin-bottom: 0.8rem; font-size: 0.85rem; font-weight: 700; color: rgba(255,255,255,0.4); text-transform: uppercase; }
        .input-group input, .input-group textarea { width: 100%; padding: 1.1rem; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); border-radius: 18px; color: #fff; outline: none; transition: 0.3s; }
        .input-group input:focus, .input-group textarea:focus { border-color: var(--primary-red); }
        .input-group textarea { resize: vertical; min-height: 100px; }

        .btn-save { 
            background: linear-gradient(135deg, var(--primary-red), var(--accent-red)); 
            color: white; padding: 1rem 3rem; border-radius: 15px; border: none; font-weight: 800; 
            cursor: pointer; display: flex; align-items: center; gap: 10px; transition: 0.4s; 
            box-shadow: 0 10px 20px rgba(211, 47, 47, 0.3); font-size: 1.1rem;
        }
        .btn-save:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(211, 47, 47, 0.5); }

        
        
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; visibility: hidden; } }
        
        .preview-img { width: 100px; height: 100px; object-fit: contain; border-radius: 10px; background: rgba(0,0,0,0.5); padding: 5px; margin-top: 10px; border: 1px dashed rgba(255,255,255,0.2); }
            .msg-toast { 
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            background: rgba(76, 175, 80, 0.95); border: 1px solid #4CAF50; color: #fff; 
            padding: 1rem 2rem; border-radius: 12px; font-weight: 600; font-size: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: flex; align-items: center; gap: 10px;
            animation: slideIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275), fadeOut 0.5s ease-in 3.5s forwards;
        }
        .msg-toast.error { background: rgba(211, 47, 47, 0.95); border-color: #D32F2F; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; visibility: hidden; } }
</style>
</head>
<body>

<aside class="sidebar">
    <?php if(!empty($current_settings['logo']) && file_exists('../' . $current_settings['logo'])): ?>
        <div style="text-align: center; margin-bottom: 4rem;">
            <img src="../<?php echo $current_settings['logo']; ?>" alt="Logo" style="width: <?php echo !empty($current_settings['admin_logo_width']) ? htmlspecialchars($current_settings['admin_logo_width']) : '200px'; ?>; max-width: 100%; object-fit: contain; filter: drop-shadow(0 0 20px rgba(211, 47, 47, 0.3));">
        </div>
    <?php else: ?>
        <div class="sidebar-logo">ORAX</div>
    <?php endif; ?>
    
    <ul class="side-nav">
        <li><a href="dashboard.php"><i class="fas fa-th-large"></i> <?php echo $t['dashboard']; ?></a></li>
        <li><a href="videos.php"><i class="fas fa-video"></i> <?php echo $t['videos']; ?></a></li>
        <li><a href="categories.php"><i class="fas fa-folder"></i> <?php echo $t['categories']; ?></a></li>
        <li><a href="users.php"><i class="fas fa-user-friends"></i> <?php echo $t['users']; ?></a></li>
        <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> <?php echo $t['settings']; ?></a></li>
    </ul>

    <div style="margin-top: auto;">
        <div class="lang-pills-admin" style="margin-bottom: 1.5rem;">
            <a href="?lang=en" class="<?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
            <a href="?lang=tr" class="<?php echo $lang == 'tr' ? 'active' : ''; ?>">TR</a>
        </div>
        <a href="logout.php" style="text-decoration: none; display: flex; align-items: center; gap: 1.2rem; padding: 1.2rem 1.5rem; background: rgba(255,255,255,0.03); border-radius: 15px; color: #888; width: 100%; font-weight: 600; transition: 0.3s;" onmouseover="this.style.background='rgba(211,47,47,0.1)'; this.style.color='var(--primary-red)';" onmouseout="this.style.background='rgba(255,255,255,0.03)'; this.style.color='#888';">
            <i class="fas fa-power-off"></i> <?php echo $t['logout']; ?>
        </a>
    </div>
</aside>

<main class="main-pane">
    <header class="header-bar">
        <h1><?php echo $t['title']; ?></h1>
        <div style="display: flex; gap: 1rem;">
            <a href="../index.php" target="_blank" class="btn btn-primary" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); box-shadow: none;">
                <i class="fas fa-external-link-alt"></i> <?php echo $t['view_site']; ?>
            </a>
        </div>
    </header>
    <?php if(isset($_GET['msg'])): ?>
        <div class="msg-toast <?php echo $_GET['msg'] == 'error' ? 'error' : ''; ?>">
            <i class="fas <?php echo $_GET['msg'] == 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
            <?php 
                if($_GET['msg'] == 'success') echo isset($t['saved']) ? $t['saved'] : 'İşlem Başarılı!';
                else echo 'Bir hata oluştu!';
            ?>
        </div>
        <script>
            setTimeout(() => {
                const url = new URL(window.location);
                url.searchParams.delete('msg');
                window.history.replaceState({}, document.title, url);
            }, 4000);
        </script>
    <?php endif; ?>

    

    <div class="tabs">
        <div class="tab active" onclick="switchTab('general')"><i class="fas fa-sliders-h"></i> <?php echo $t['tab_general']; ?></div>
        <div class="tab" onclick="switchTab('seo')"><i class="fas fa-search"></i> <?php echo $t['tab_seo']; ?></div>
        <div class="tab" onclick="switchTab('theme')"><i class="fas fa-paint-brush"></i> <?php echo $t['tab_theme']; ?></div>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_settings">

        <!-- General Tab -->
        <div class="tab-content active" id="tab-general">
            <div class="settings-card">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div class="input-group">
                        <label><?php echo $t['site_title']; ?></label>
                        <input type="text" name="site_title" value="<?php echo htmlspecialchars($current_settings['site_title'] ?? 'Orax Tube'); ?>">
                    </div>
                    <div class="input-group">
                        <label><?php echo $t['site_tagline']; ?></label>
                        <input type="text" name="site_tagline" value="<?php echo htmlspecialchars($current_settings['site_tagline'] ?? 'Premium HD Adult Videos'); ?>">
                    </div>
                </div>
                <div class="input-group">
                    <label><?php echo $t['contact_email']; ?></label>
                    <input type="email" name="contact_email" value="<?php echo htmlspecialchars($current_settings['contact_email'] ?? 'admin@oraxtube.com'); ?>">
                </div>
                <div class="input-group" style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 15px; border: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <label style="margin-bottom: 0; font-size: 1rem; color: #fff;"><i class="fas fa-exclamation-triangle" style="color: var(--primary-red);"></i> <?php echo $t['age_warning']; ?></label>
                        <p style="font-size: 0.8rem; opacity: 0.5; margin-top: 5px;">Shows a disclaimer popup to first-time visitors.</p>
                    </div>
                    <label class="switch" style="position: relative; display: inline-block; width: 50px; height: 28px;">
                        <input type="checkbox" name="age_warning" value="1" <?php echo (isset($current_settings['age_warning']) && $current_settings['age_warning'] == '1') ? 'checked' : ''; ?> style="opacity: 0; width: 0; height: 0;">
                        <span class="slider round" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.1); transition: .4s; border-radius: 34px;"></span>
                        <style>
                            .switch input:checked + .slider { background-color: var(--primary-red); }
                            .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
                            .switch input:checked + .slider:before { transform: translateX(22px); }
                        </style>
                    </label>
                </div>
            </div>
        </div>

        <!-- SEO Tab -->
        <div class="tab-content" id="tab-seo">
            <div class="settings-card">
                <div class="input-group">
                    <label><?php echo $t['seo_desc']; ?></label>
                    <textarea name="seo_desc" rows="4"><?php echo htmlspecialchars($current_settings['seo_desc'] ?? 'Experience the best high-definition premium adult content, free sex videos, and exclusive porn updates everyday on Orax Tube.'); ?></textarea>
                </div>
                <div class="input-group">
                    <label><?php echo $t['seo_keywords']; ?></label>
                    <input type="text" name="seo_keywords" value="<?php echo htmlspecialchars($current_settings['seo_keywords'] ?? 'porn, adult, xxx, hd porn, free sex videos, premium tube, amateur, teen, mature'); ?>">
                </div>
                <div class="input-group">
                    <label><i class="fas fa-chart-line" style="color: #4caf50;"></i> <?php echo $t['google_analytics']; ?></label>
                    <textarea name="google_analytics" rows="4" placeholder="<script>...G-XXXXXXX...</script> Veya sadece UA-XXXXX kodunu da yazabilirsiniz."><?php echo htmlspecialchars($current_settings['google_analytics'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Theme Tab -->
        <div class="tab-content" id="tab-theme">
            <div class="settings-card">
                <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <div class="input-group">
                            <label><i class="fas fa-star"></i> <?php echo $t['favicon']; ?> <span style="font-size:0.7rem; opacity:0.5; text-transform:none;">(Önerilen: 32x32 px Kare, PNG/ICO)</span></label>
                            <input type="file" name="favicon" accept="image/png, image/jpeg, image/x-icon">
                            <?php if(!empty($current_settings['favicon']) && file_exists('../' . $current_settings['favicon'])): ?>
                                <img src="../<?php echo $current_settings['favicon']; ?>" class="preview-img">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div>
                        <div class="input-group">
                            <label><i class="fas fa-images"></i> <?php echo $t['logo']; ?> <span style="font-size:0.7rem; opacity:0.5; text-transform:none;">(Önerilen: Şeffaf Arkaplan, Geniş format)</span></label>
                            <input type="file" name="logo" accept="image/png, image/jpeg, image/svg+xml">
                            <?php if(!empty($current_settings['logo']) && file_exists('../' . $current_settings['logo'])): ?>
                                <img src="../<?php echo $current_settings['logo']; ?>" class="preview-img" style="width: auto; max-width: 200px;">
                            <?php endif; ?>
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-arrows-alt-h"></i> <?php echo $t['logo_width']; ?></label>
                            <input type="text" name="logo_width" value="<?php echo htmlspecialchars($current_settings['logo_width'] ?? '150px'); ?>" placeholder="Örn: 150px veya 5rem">
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-chess-board"></i> <?php echo $t['admin_logo_width']; ?></label>
                            <input type="text" name="admin_logo_width" value="<?php echo htmlspecialchars($current_settings['admin_logo_width'] ?? '200px'); ?>" placeholder="Örn: 200px">
                        </div>
                        <div class="input-group" style="margin-top: 2rem;">
                            <label><i class="fas fa-palette"></i> <?php echo $t['primary_color']; ?></label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="color" name="primary_color" value="<?php echo htmlspecialchars($current_settings['primary_color'] ?? '#D32F2F'); ?>" style="padding: 0; width: 50px; height: 50px; border: none; background: transparent; cursor: pointer;">
                                <span style="font-family: monospace; font-size: 1.1rem; opacity: 0.8;"><?php echo htmlspecialchars($current_settings['primary_color'] ?? '#D32F2F'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="input-group" style="margin-top: 2rem;">
                    <label><?php echo $t['custom_css']; ?></label>
                    <textarea name="custom_css" rows="6" placeholder="body { ... }"><?php echo htmlspecialchars($current_settings['custom_css'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-save"><i class="fas fa-save"></i> <?php echo $t['save']; ?></button>
    </form>
</main>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        event.currentTarget.classList.add('active');
        document.getElementById('tab-' + tabId).classList.add('active');
    }
</script>

</body>
</html>
