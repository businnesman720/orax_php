<?php
include 'auth.php';
include '../includes/db.php';

if (!isset($_SESSION['admin_lang'])) {
    $_SESSION['admin_lang'] = 'tr';
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
        'title' => 'Ads Management',
        'tab_slots' => 'Ad Slots',
        'tab_stats' => 'Ad Analytics',
        'ad_pre_roll' => 'Pre-roll Video Ad',
        'ad_header' => 'Header Banner',
        'ad_sidebar' => 'Sidebar Banner',
        'ad_video_bottom' => 'Video Under Banner',
        'ad_pop_under' => 'Pop-under Ad',
        'type_code' => 'HTML/JS Code',
        'type_image' => 'Image Upload',
        'type_video' => 'Video Upload',
        'ad_status' => 'Status',
        'ad_link' => 'Link URL',
        'ad_code' => 'Ad Code (Script/Html)',
        'ad_file' => 'Upload File',
        'active' => 'Active',
        'disabled' => 'Disabled',
        'save' => 'Save Ad Settings',
        'success' => 'Ads updated successfully!',
        'note_betting' => 'Tip: Compatible with betting site banners and external ad networks (JuicyAds, ExoClick etc.)'
    ],
    'tr' => [
        'title' => 'Reklam Yönetimi',
        'tab_slots' => 'Reklam Alanları',
        'tab_stats' => 'İstatistikler',
        'ad_pre_roll' => 'Video Öncesi (Pre-roll)',
        'ad_header' => 'Üst Banner (Header)',
        'ad_sidebar' => 'Yan Menü (Sidebar)',
        'ad_video_bottom' => 'Video Altı Banner',
        'ad_pop_under' => 'Pop-under (Açılır Pencere)',
        'type_code' => 'Kod (HTML/JS)',
        'type_image' => 'Resim Yükle',
        'type_video' => 'Video Yükle',
        'ad_status' => 'Durum',
        'ad_link' => 'Yönlendirme Linki',
        'ad_code' => 'Reklam Kodu (Script)',
        'ad_file' => 'Dosya Seç',
        'active' => 'Aktif',
        'disabled' => 'Devre Dışı',
        'save' => 'Reklamları Kaydet',
        'success' => 'Reklamlar başarıyla güncellendi!',
        'note_betting' => 'İpucu: Bahis siteleri bannerları ve dış reklam ağları (JuicyAds, ExoClick vb.) ile tam uyumludur.'
    ]
];
$t = $texts[$lang];

// Handle Posts
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_ads') {
    $keys = ['pre_roll', 'header_banner', 'sidebar_banner', 'video_bottom', 'pop_under'];
    
    foreach ($keys as $key) {
        $type = $_POST["type_$key"] ?? 'code';
        $status = isset($_POST["status_$key"]) ? 1 : 0;
        $link = $_POST["link_$key"] ?? '';
        $code = $_POST["code_$key"] ?? '';
        $file_path = $_POST["existing_file_$key"] ?? '';

        // Handle File Uploads
        if ($type != 'code' && !empty($_FILES["file_$key"]['name'])) {
            $folder = ($type == 'video' ? 'uploads/ads/videos/' : 'uploads/ads/images/');
            if (!is_dir('../' . $folder)) mkdir('../' . $folder, 0777, true);
            
            $ext = pathinfo($_FILES["file_$key"]['name'], PATHINFO_EXTENSION);
            $new_name = $key . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES["file_$key"]['tmp_name'], '../' . $folder . $new_name)) {
                $file_path = $folder . $new_name;
            }
        }

        $content = ($type == 'code') ? $code : $file_path;

        $stmt = $pdo->prepare("REPLACE INTO ads (ad_key, ad_type, content, link_url, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$key, $type, $content, $link, $status]);
    }
    header("Location: ads.php?msg=success");
    exit;
}

// Get current ads
$stmt = $pdo->query("SELECT * FROM ads");
$current_ads = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $current_ads[$row['ad_key']] = $row;
}

// Settings for theme color
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE setting_key = 'primary_color'");
$p_color = $stmt_settings->fetch();
$primary_color = $p_color ? $p_color['setting_value'] : '#D32F2F';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORAX - <?php echo $t['title']; ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --admin-sidebar: #151515;
            --admin-content: #1e1e1e;
            --p-color: <?php echo $primary_color; ?>;
            --primary-red: <?php echo $primary_color; ?>;
        }

        body {
            background-color: var(--admin-content);
            margin: 0; padding: 0;
            display: flex; height: 100vh;
            overflow: hidden;
            font-family: 'Outfit', sans-serif;
            color: white;
        }

        /* Sidebar styling - MATCHING DASHBOARD */
        .sidebar {
            width: 300px;
            background: var(--admin-sidebar);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            display: flex; flex-direction: column;
            padding: 2.5rem;
            transition: 0.4s;
            z-index: 100;
            flex-shrink: 0;
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
        .lang-pills-admin a { text-decoration: none; color: white; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; opacity: 0.4; transition: 0.3s; }
        .lang-pills-admin a.active { background: var(--primary-red); opacity: 1; }

        .main-pane {
            flex: 1; padding: 3rem; overflow-y: auto;
            background: radial-gradient(circle at top right, rgba(211, 47, 47, 0.03) 0%, rgba(30, 30, 30, 1) 50%);
        }

        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header-bar h1 { font-size: 2.5rem; font-weight: 800; margin: 0; }

        .tabs { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 0.5rem; }
        .tab {
            padding: 1rem 2rem; cursor: pointer; border-radius: 15px; color: white; opacity: 0.5;
            font-weight: 700; transition: 0.3s;
        }
        .tab.active { opacity: 1; background: var(--p-color); }

        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.4s ease; }

        .ad-card {
            background: rgba(255, 255, 255, 0.02);
            padding: 2.5rem; border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 2.5rem;
            transition: 0.4s;
        }
        .ad-card:hover { border-color: rgba(211, 47, 47, 0.2); box-shadow: 0 10px 30px rgba(0,0,0,0.2); }

        .ad-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 1rem; }
        .ad-card-header h3 { margin: 0; font-size: 1.25rem; }

        .input-group { margin-bottom: 1.5rem; }
        .input-group label { display: block; margin-bottom: 0.6rem; font-size: 0.75rem; font-weight: 700; color: rgba(255,255,255,0.4); text-transform: uppercase; }
        .input-group input, .input-group textarea, .input-group select { 
            width: 100%; padding: 1rem; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; color: #fff; outline: none; transition: 0.3s; 
        }
        .input-group input:focus, .input-group textarea:focus { border-color: var(--p-color); }

        .switch-box { display: flex; align-items: center; gap: 10px; }
        .switch { position: relative; display: inline-block; width: 45px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.1); transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--p-color); }
        input:checked + .slider:before { transform: translateX(21px); }

        .btn-save { 
            background: linear-gradient(135deg, var(--p-color), #ff5252); 
            color: white; padding: 1.2rem 3.5rem; border-radius: 18px; border: none; font-weight: 800; 
            cursor: pointer; display: flex; align-items: center; gap: 12px; transition: 0.4s; 
            box-shadow: 0 10px 25px rgba(211, 47, 47, 0.3); font-size: 1.1rem;
            position: fixed; bottom: 40px; right: 40px; z-index: 1000;
        }
        .btn-save:hover { transform: translateY(-5px) scale(1.05); box-shadow: 0 15px 35px rgba(211, 47, 47, 0.5); }

        .msg-toast { 
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            background: rgba(76, 175, 80, 0.95); border: 1px solid #4CAF50; color: #fff; 
            padding: 1rem 2rem; border-radius: 12px; font-weight: 600; animation: slideIn 0.5s ease forwards;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }

        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .stats-card { background: rgba(255,255,255,0.02); border-radius: 25px; padding: 2rem; border: 1px solid rgba(255,255,255,0.05); }

        .preview-mini { height: 80px; object-fit: contain; margin-top: 10px; border-radius: 10px; border: 1px dashed rgba(255,255,255,0.2); background: #000; padding: 5px; }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="main-pane">
    <header class="header-bar">
        <h1><?php echo $t['title']; ?></h1>
        <p style="opacity: 0.5;"><?php echo $t['note_betting']; ?></p>
    </header>

    <?php if(isset($_GET['msg'])): ?>
        <div class="msg-toast"><?php echo $t['success']; ?></div>
        <script>setTimeout(() => { window.location.href='ads.php'; }, 3000);</script>
    <?php endif; ?>

    <div class="tabs">
        <div class="tab active" onclick="switchTab('slots')"><i class="fas fa-layer-group"></i> <?php echo $t['tab_slots']; ?></div>
        <div class="tab" onclick="switchTab('stats')"><i class="fas fa-chart-pie"></i> <?php echo $t['tab_stats']; ?></div>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_ads">
        
        <div class="tab-content active" id="tab-slots">
            <?php 
            $slots = [
                'pre_roll' => ['icon' => 'play-circle', 'name' => $t['ad_pre_roll']],
                'header_banner' => ['icon' => 'window-maximize', 'name' => $t['ad_header']],
                'sidebar_banner' => ['icon' => 'columns', 'name' => $t['ad_sidebar']],
                'video_bottom' => ['icon' => 'arrow-down', 'name' => $t['ad_video_bottom']],
                'pop_under' => ['icon' => 'external-link-alt', 'name' => $t['ad_pop_under']]
            ];

            foreach ($slots as $key => $slot): 
                $ad = $current_ads[$key] ?? ['ad_type' => 'code', 'content' => '', 'link_url' => '', 'status' => 0];
            ?>
                <div class="ad-card">
                    <div class="ad-card-header">
                        <h3><i class="fas fa-<?php echo $slot['icon']; ?>" style="color: var(--p-color);"></i> <?php echo $slot['name']; ?></h3>
                        <div class="switch-box">
                            <span style="font-size: 0.75rem; font-weight: 700; opacity: 0.4;"><?php echo $ad['status'] ? $t['active'] : $t['disabled']; ?></span>
                            <label class="switch">
                                <input type="checkbox" name="status_<?php echo $key; ?>" <?php echo $ad['status'] ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-grid" style="display: grid; grid-template-columns: 250px 1fr; gap: 2rem;">
                        <div>
                            <div class="input-group">
                                <label><?php echo $t['type_code']; ?></label>
                                <select name="type_<?php echo $key; ?>" onchange="toggleAdType('<?php echo $key; ?>', this.value)">
                                    <option value="code" <?php echo $ad['ad_type'] == 'code' ? 'selected' : ''; ?>><?php echo $t['type_code']; ?></option>
                                    <option value="image" <?php echo $ad['ad_type'] == 'image' ? 'selected' : ''; ?>><?php echo $t['type_image']; ?></option>
                                    <?php if($key == 'pre_roll'): ?>
                                        <option value="video" <?php echo $ad['ad_type'] == 'video' ? 'selected' : ''; ?>><?php echo $t['type_video']; ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div id="link_div_<?php echo $key; ?>" style="<?php echo $ad['ad_type'] == 'code' ? 'display:none;' : ''; ?>">
                                <div class="input-group">
                                    <label><?php echo $t['ad_link']; ?></label>
                                    <input type="text" name="link_url_<?php echo $key; ?>" value="<?php echo htmlspecialchars($ad['link_url'] ?? ''); ?>" placeholder="https://...">
                                </div>
                            </div>
                        </div>

                        <div>
                            <div id="code_div_<?php echo $key; ?>" style="<?php echo $ad['ad_type'] == 'code' ? '' : 'display:none;'; ?>">
                                <div class="input-group">
                                    <label><?php echo $t['ad_code']; ?></label>
                                    <textarea name="code_<?php echo $key; ?>" rows="5" placeholder="<script>...</script>"><?php echo htmlspecialchars($ad['ad_type'] == 'code' ? $ad['content'] : ''); ?></textarea>
                                </div>
                            </div>

                            <div id="file_div_<?php echo $key; ?>" style="<?php echo $ad['ad_type'] != 'code' ? '' : 'display:none;'; ?>">
                                <div class="input-group">
                                    <label><?php echo $t['ad_file']; ?></label>
                                    <input type="file" name="file_<?php echo $key; ?>" accept="<?php echo $ad['ad_type'] == 'video' ? 'video/*' : 'image/*'; ?>">
                                    <input type="hidden" name="existing_file_<?php echo $key; ?>" value="<?php echo $ad['ad_type'] != 'code' ? $ad['content'] : ''; ?>">
                                    <?php if($ad['ad_type'] != 'code' && !empty($ad['content'])): ?>
                                        <div style="margin-top: 10px;">
                                            <span style="font-size: 0.7rem; opacity: 0.5;">Mevcut: <?php echo $ad['content']; ?></span><br>
                                            <?php if($ad['ad_type'] == 'image'): ?>
                                                <img src="../<?php echo $ad['content']; ?>" class="preview-mini">
                                            <?php else: ?>
                                                <i class="fas fa-video" style="color: var(--p-color);"></i>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="tab-content" id="tab-stats">
            <div class="stats-grid">
                <div class="stats-card">
                    <h3 style="margin-bottom: 2rem;"><i class="fas fa-chart-pie" style="color:var(--p-color)"></i> Slot Dağılımı</h3>
                    <canvas id="adsChart" style="max-height: 300px;"></canvas>
                </div>
                <div class="stats-card">
                    <h3>Son Etiketler (İpucu)</h3>
                    <p style="opacity:0.6; line-height: 1.8;">
                        * ExoClick veya JuicyAds gibi platformlardan "Popunder" kodu alıp sadece pop-under alanına yapıştırmanız yeterlidir.<br>
                        * Kendi reklam videonuzu (Pre-roll) yüklerken boyutuna dikkat edin (Maks 10MB önerilir).<br>
                        * Banner alanları standart 728x90 ve 300x250 boyutları için optimize edilmiştir.
                    </p>
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

    function toggleAdType(key, type) {
        const codeDiv = document.getElementById('code_div_' + key);
        const fileDiv = document.getElementById('file_div_' + key);
        const linkDiv = document.getElementById('link_div_' + key);

        if (type === 'code') {
            codeDiv.style.display = 'block';
            fileDiv.style.display = 'none';
            linkDiv.style.display = 'none';
        } else {
            codeDiv.style.display = 'none';
            fileDiv.style.display = 'block';
            linkDiv.style.display = 'block';
        }
    }

    // Chart.js
    const ctx = document.getElementById('adsChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Aktif', 'Pasif'],
            datasets: [{
                data: [<?php 
                    $active = 0; $passive = 0;
                    foreach($current_ads as $a) if($a['status']) $active++; else $passive++;
                    echo "$active, $passive";
                ?>],
                backgroundColor: ['<?php echo $primary_color; ?>', 'rgba(255,255,255,0.1)'],
                borderWidth: 0
            }]
        },
        options: {
            cutout: '80%',
            plugins: { legend: { position: 'bottom', labels: { color: 'white' } } }
        }
    });
</script>

</body>
</html>
