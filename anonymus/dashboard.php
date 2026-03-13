<?php
include 'auth.php';
include '../includes/db.php';

// Dil ayarı (Rule 5: default English)
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
        'title' => 'Admin Dashboard',
        'welcome' => 'Welcome, Master Admin',
        'stats_video' => 'Total Videos',
        'stats_user' => 'Registered Users',
        'stats_view' => 'Total Views',
        'chart_pie' => 'Category Distribution',
        'chart_line' => 'Traffic Analysis',
        'logout' => 'Safe Logout',
        'view_site' => 'Live View',
        'dashboard' => 'Dashboard',
        'videos' => 'Videos',
        'categories' => 'Categories',
        'users' => 'Users',
        'settings' => 'Site Settings',
        'reports' => 'Reports',
        'payment_requests' => 'Payment Requests',
        'payment_methods' => 'Payment Methods'
    ],
    'tr' => [
        'title' => 'Yönetim Paneli',
        'welcome' => 'Hoş Geldin, Ana Yönetici',
        'stats_video' => 'Toplam Video',
        'stats_user' => 'Kayıtlı Kullanıcı',
        'stats_view' => 'Toplam İzlenme',
        'chart_pie' => 'Kategori Dağılımı',
        'chart_line' => 'Trafik Analizi',
        'logout' => 'Güvenli Çıkış',
        'view_site' => 'Siteyi Gör',
        'dashboard' => 'Panel',
        'videos' => 'Videolar',
        'categories' => 'Kategoriler',
        'users' => 'Kullanıcılar',
        'settings' => 'Site Ayarları',
        'reports' => 'Raporlar',
        'payment_requests' => 'Bakiye Talepleri',
        'payment_methods' => 'Ödeme Yöntemleri'
    ]
];
$t = $texts[$lang];

// Veritabanından gerçek istatistikler alalım
function formatNumber($num) {
    if($num >= 1000000) return round($num/1000000, 1) . 'M';
    if($num >= 1000) return round($num/1000, 1) . 'K';
    return $num;
}

// Stats
$video_count = $pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn();
$video_this_month = $pdo->query("SELECT COUNT(*) FROM videos WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();

$user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$user_today = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURRENT_DATE()")->fetchColumn();

$view_count_raw = $pdo->query("SELECT SUM(views) FROM videos")->fetchColumn();
if (!$view_count_raw) $view_count_raw = 0;
$view_count = formatNumber($view_count_raw);

// Pie Chart (Kategori Dağılımı)
$cat_col = $lang == 'tr' ? 'name_tr' : 'name_en';
$cat_stmt = $pdo->query("
    SELECT c.$cat_col as label, COUNT(v.id) as count 
    FROM categories c 
    INNER JOIN videos v ON c.id = v.category_id 
    GROUP BY c.id
");
$cat_data = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

$pie_labels = [];
$pie_values = [];
foreach($cat_data as $row) {
    $pie_labels[] = $row['label'];
    $pie_values[] = $row['count'];
}
// Eğer hiç video yoksa boş görünmesin diye
if (empty($pie_labels)) {
    $pie_labels = ['No Data'];
    $pie_values = [1];
}

// Line Chart (Son 6 ayın trendi - videolardan gelen izlenmeler)
$line_labels = [];
$line_values = [];
for($i = 5; $i >= 0; $i--) {
    $month = date('m', strtotime("-$i months"));
    $year = date('Y', strtotime("-$i months"));
    // Aylar TR/EN
    $monthName = $lang == 'tr' ? 
        ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'][(int)$month - 1] : 
        date('M', strtotime("-$i months"));
        
    $stmt = $pdo->prepare("SELECT SUM(views) FROM videos WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?");
    $stmt->execute([$month, $year]);
    $views = $stmt->fetchColumn();
    
    $line_labels[] = $monthName;
    $line_values[] = (int)$views;
}
// VIP User Stats
$vip_count = $pdo->query("SELECT COUNT(*) FROM users WHERE is_vip = 1")->fetchColumn();
$regular_count = $user_count - $vip_count;

// Get current settings
$stmt_settings = $pdo->query("SELECT * FROM settings");
$current_settings = [];
while ($row = $stmt_settings->fetch(PDO::FETCH_ASSOC)) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORAX - Premium Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Sidebar styling */
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

        /* Main Area */
        .main-pane {
            flex: 1; padding: 3rem; overflow-y: auto;
            background: radial-gradient(circle at top right, rgba(211, 47, 47, 0.03) 0%, rgba(30, 30, 30, 1) 50%);
        }

        .header-bar {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 4rem;
        }

        .header-bar h1 { font-size: 2.5rem; font-weight: 800; }

        /* Stats Cards */
        .dashboard-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;
            margin-bottom: 3rem;
        }

        .premium-card {
            background: rgba(255, 255, 255, 0.02);
            padding: 2.5rem; border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: 0.4s; position: relative; overflow: hidden;
        }
        .premium-card:hover { border-color: var(--primary-red); transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
        .premium-card i { position: absolute; right: -20px; bottom: -20px; font-size: 8rem; opacity: 0.05; color: var(--primary-red); }

        .stat-val { font-size: 3rem; font-weight: 900; color: white; margin: 1rem 0; }
        .stat-label { color: rgba(255,255,255,0.4); text-transform: uppercase; font-weight: 700; font-size: 0.85rem; letter-spacing: 2px; }

        /* Charts Section */
        .charts-container {
            display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem;
        }
        .chart-box {
            background: rgba(255, 255, 255, 0.02); padding: 2.5rem; border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .chart-box h3 { margin-bottom: 2rem; font-size: 1.2rem; opacity: 0.8; }

        /* Indicators */
        .pulse-indicator {
            width: 12px; height: 12px; border-radius: 50%; background: #4caf50;
            display: inline-block; box-shadow: 0 0 10px #4caf50;
            animation: pulse-green 2s infinite; margin-right: 10px;
        }
        @keyframes pulse-green {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(76, 175, 80, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
        }

        .lang-pills-admin { display: flex; gap: 0.5rem; background: rgba(0,0,0,0.3); padding: 0.3rem; border-radius: 50px; }
        .lang-pills-admin a { text-decoration: none; color: white; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; opacity: 0.4; }
        .lang-pills-admin a.active { background: var(--primary-red); opacity: 1; }

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

<?php include 'includes/sidebar.php'; ?>

<main class="main-pane">
    <header class="header-bar">
        <div>
            <h1><?php echo $t['welcome']; ?></h1>
            <p style="opacity: 0.5; margin-top: 0.5rem;"><span class="pulse-indicator"></span> System is Online • Full Control Enabled</p>
        </div>
        <div style="display: flex; gap: 1rem;">
            <button class="btn-icon" style="background: rgba(255,255,255,0.05); cursor: pointer;"><i class="fas fa-bell"></i></button>
            <a href="../index.php" target="_blank" class="btn btn-primary" style="display: flex; align-items: center; gap: 10px;">
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

    <div class="dashboard-grid">
        <div class="premium-card">
            <i class="fas fa-play-circle"></i>
            <div class="stat-label"><?php echo $t['stats_video']; ?></div>
            <div class="stat-val"><?php echo $video_count; ?></div>
            <div style="color: #4caf50; font-size: 0.85rem; font-weight: 700;">+ <?php echo $video_this_month; ?> <?php echo $lang == 'tr' ? 'Bu ay' : 'This month'; ?></div>
        </div>
        <div class="premium-card">
            <i class="fas fa-eye"></i>
            <div class="stat-label"><?php echo $t['stats_view']; ?></div>
            <div class="stat-val"><?php echo $view_count; ?></div>
            <div style="color: #4caf50; font-size: 0.85rem; font-weight: 700;">&nbsp;</div>
        </div>
        <div class="premium-card">
            <i class="fas fa-crown"></i>
            <div class="stat-label"><?php echo $lang == 'tr' ? 'VIP Üyeler' : 'VIP Members'; ?></div>
            <div class="stat-val"><?php echo $vip_count; ?></div>
            <div style="color: #ffc107; font-size: 0.85rem; font-weight: 700;">Premium Access</div>
        </div>
    </div>

    <div class="charts-container">
        <div class="chart-box">
            <h3><?php echo $t['chart_line']; ?></h3>
            <canvas id="mainChart" height="250"></canvas>
        </div>
        <div class="chart-box">
            <h3><?php echo $t['chart_pie']; ?></h3>
            <canvas id="pieChart" height="250"></canvas>
        </div>
        <div class="chart-box">
            <h3><?php echo $lang == 'tr' ? 'VIP Dağılımı' : 'VIP Distribution'; ?></h3>
            <canvas id="vipChart" height="250"></canvas>
        </div>
    </div>
</main>

<script>
    const lineLabels = <?php echo json_encode($line_labels); ?>;
    const lineValues = <?php echo json_encode($line_values); ?>;
    
    const pieLabels = <?php echo json_encode($pie_labels); ?>;
    const pieValues = <?php echo json_encode($pie_values); ?>;

    // Dinamik renk paleti (Pasta grafik için)
    const baseColors = ['#D32F2F', '#FF5252', '#B71C1C', '#8B0000', '#F44336', '#E53935'];
    const pieColors = pieValues.map((_, i) => baseColors[i % baseColors.length]);

    // Main Area Chart
    const ctx = document.getElementById('mainChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: lineLabels,
            datasets: [{
                label: '<?php echo $t['stats_view']; ?>',
                data: lineValues,
                borderColor: '#D32F2F',
                backgroundColor: 'rgba(211, 47, 47, 0.15)',
                fill: true,
                tension: 0.5,
                borderWidth: 4,
                pointRadius: 6,
                pointBackgroundColor: '#D32F2F'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: 'rgba(255,255,255,0.4)', font: { weight: '700' } } },
                x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.4)', font: { weight: '700' } } }
            }
        }
    });

    // Pie Chart - Rule 4
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'pie', // Using pie as requested
        data: {
            labels: pieLabels,
            datasets: [{
                data: pieValues,
                backgroundColor: pieColors,
                borderWidth: 0,
                hoverOffset: 15
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { color: 'rgba(255,255,255,0.6)', font: { size: 14, weight: '600' }, padding: 25 } }
            },
            animation: { animateRotate: true, animateScale: true }
        }
    });

    // VIP Chart
    const vipCtx = document.getElementById('vipChart').getContext('2d');
    new Chart(vipCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php echo $lang == 'tr' ? "'Normal', 'VIP'" : "'Regular', 'VIP'"; ?>],
            datasets: [{
                data: [<?php echo "$regular_count, $vip_count"; ?>],
                backgroundColor: ['#444', '#FFD700'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { color: 'rgba(255,255,255,0.6)' } } },
            animation: { animateScale: true }
        }
    });
</script>

</body>
</html>

