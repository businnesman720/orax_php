<?php
include 'auth.php';
include '../includes/db.php';

if (!isset($_SESSION['admin_lang'])) { $_SESSION['admin_lang'] = 'tr'; }
if (isset($_GET['lang'])) {
    $_SESSION['admin_lang'] = $_GET['lang'] == 'tr' ? 'tr' : 'en';
    $clean_url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $clean_url"); exit;
}
$lang = $_SESSION['admin_lang'];

$texts = [
    'en' => [
        'title' => 'Report Management',
        'dashboard' => 'Dashboard',
        'videos' => 'Videos',
        'categories' => 'Categories',
        'users' => 'Users',
        'settings' => 'Site Settings',
        'reports' => 'Reports',
        'payment_requests' => 'Payment Requests',
        'payment_methods' => 'Payment Methods',
        'report_types' => 'Report Reasons',
        'logout' => 'Logout',
        'view_site' => 'View Site',
        'video_title' => 'Video',
        'reason' => 'Reason',
        'description' => 'Description',
        'status' => 'Status',
        'date' => 'Date',
        'actions' => 'Actions',
        'pending' => 'Pending',
        'reviewed' => 'Reviewed',
        'action_taken' => 'Action Taken',
        'mark_reviewed' => 'Mark Reviewed',
        'delete' => 'Delete Report',
        'no_reports' => 'No reports found.',
        'tab_pending' => 'Pending Reports',
        'tab_reviewed' => 'Reviewed Reports',
        'no_desc' => 'No description provided.',
        'details_title' => 'Report Details'
    ],
    'tr' => [
        'title' => 'Hata Bildirimleri',
        'dashboard' => 'Panel',
        'videos' => 'Videolar',
        'categories' => 'Kategoriler',
        'users' => 'Kullanıcılar',
        'settings' => 'Site Ayarları',
        'reports' => 'Raporlar',
        'payment_requests' => 'Bakiye Talepleri',
        'payment_methods' => 'Ödeme Yöntemleri',
        'report_types' => 'Rapor Nedenleri',
        'logout' => 'Çıkış',
        'view_site' => 'Siteyi Gör',
        'video_title' => 'Video',
        'reason' => 'Neden',
        'description' => 'Açıklama',
        'status' => 'Durum',
        'date' => 'Tarih',
        'actions' => 'İşlemler',
        'pending' => 'Bekliyor',
        'reviewed' => 'İncelendi',
        'action_taken' => 'İşlem Yapıldı',
        'mark_reviewed' => 'İncelendi İşaretle',
        'delete' => 'Raporu Sil',
        'no_reports' => 'Henüz rapor yok.',
        'tab_pending' => 'Bekleyen Raporlar',
        'tab_reviewed' => 'İncelenen Raporlar',
        'no_desc' => 'Açıklama belirtilmedi.',
        'details_title' => 'Rapor Detayları'
    ]
];
$t = $texts[$lang];

// Tab Logic
$current_tab = $_GET['tab'] ?? 'pending';

// Handle Status Change
if (isset($_GET['status']) && isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?");
    $stmt->execute([$_GET['status'], $_GET['id']]);
    header("Location: reports.php?tab=" . $current_tab . "&msg=success"); exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: reports.php?tab=" . $current_tab . "&msg=success"); exit;
}

// Fetch Reports based on tab
$where = $current_tab == 'pending' ? "status = 'pending'" : "status IN ('reviewed', 'action_taken')";

if ($current_tab == 'pending') {
    $pdo->query("UPDATE reports SET is_admin_seen = 1 WHERE status = 'pending'");
}

$reports = $pdo->query("SELECT r.*, v.title_tr as video_name, rt.name_tr as reason_tr, rt.name_en as reason_en, u.username 
                      FROM reports r 
                      LEFT JOIN videos v ON r.video_id = v.id 
                      LEFT JOIN report_types rt ON r.report_type_id = rt.id 
                      LEFT JOIN users u ON r.user_id = u.id 
                      WHERE r.$where
                      ORDER BY r.created_at DESC")->fetchAll();

// Settings for Logo
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
    <title><?php echo $t['title']; ?> - Orax Admin</title>
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

        .side-nav { list-style: none; padding: 0; flex: 1; }
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
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;
        }

        .header-bar h1 { font-size: 2.5rem; font-weight: 800; }

        .admin-tabs { display: flex; gap: 1rem; margin-bottom: 3rem; background: rgba(0,0,0,0.2); padding: 0.5rem; border-radius: 20px; width: fit-content; }
        .admin-tab { padding: 0.8rem 2rem; border-radius: 15px; text-decoration: none; color: white; opacity: 0.5; font-weight: 700; transition: 0.3s; }
        .admin-tab.active { opacity: 1; background: var(--primary-red); box-shadow: 0 5px 15px rgba(211,47,47,0.3); }

        .report-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; margin-top: 1rem; }
        .report-table th { text-align: left; padding: 1rem; opacity: 0.4; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }
        .report-row { background: rgba(255,255,255,0.02); transition: 0.3s; cursor: pointer; }
        .report-row:hover { background: rgba(255,255,255,0.05); transform: translateX(5px); }
        .report-row td { padding: 1.5rem 1rem; border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03); }
        .report-row td:first-child { border-left: 1px solid rgba(255,255,255,0.03); border-radius: 15px 0 0 15px; }
        .report-row td:last-child { border-right: 1px solid rgba(255,255,255,0.03); border-radius: 0 15px 15px 0; }
        
        .status-badge { padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
        .status-pending { background: rgba(255, 152, 0, 0.1); color: #FF9800; }
        .status-reviewed { background: rgba(33, 150, 243, 0.1); color: #2196F3; }
        .status-action_taken { background: rgba(76, 175, 80, 0.1); color: #4CAF50; }
        
        .btn-circle { width: 40px; height: 40px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.1); background: none; color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s; text-decoration: none; }
        .btn-circle:hover { background: var(--primary-red); border-color: var(--primary-red); }
        
        .lang-pills-admin { display: flex; gap: 0.5rem; background: rgba(0,0,0,0.3); padding: 0.3rem; border-radius: 50px; }
        .lang-pills-admin a { text-decoration: none; color: white; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; opacity: 0.4; }
        .lang-pills-admin a.active { background: var(--primary-red); opacity: 1; }
        
        .btn-primary { background: var(--primary-red); color: white; padding: 1rem 2rem; border-radius: 12px; text-decoration: none; border: none; font-weight: 700; transition: 0.3s; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(211, 47, 47, 0.3); }

        .video-link { color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; }
        .video-link:hover { color: var(--primary-red); }

        /* Dialog Styling */
        .dialog-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 2000; display: none; align-items: center; justify-content: center; padding: 2rem; }
        .dialog-card { border-radius: 30px; background: #1a1a1a; width: 100%; max-width: 600px; border: 1px solid rgba(255,255,255,0.05); padding: 3rem; transform: scale(0.9); opacity: 0; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative;}
        .dialog-card.active { transform: scale(1); opacity: 1; }
        .detail-row { margin-bottom: 2rem; }
        .detail-row label { display: block; font-size: 0.75rem; opacity: 0.3; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem; font-weight: 800; }
        .detail-row span { font-size: 1.1rem; font-weight: 600; display: block; }
        .detail-desc { background: rgba(0,0,0,0.3); padding: 1.5rem; border-radius: 15px; line-height: 1.6; opacity: 0.8; }

        .msg-toast { 
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            background: rgba(76, 175, 80, 0.95); border: 1px solid #4CAF50; color: #fff; 
            padding: 1rem 2rem; border-radius: 12px; font-weight: 600; font-size: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: flex; align-items: center; gap: 10px;
            animation: slideIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275), fadeOut 0.5s ease-in 3.5s forwards;
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="main-pane">
    <header class="header-bar">
        <div>
            <h1><?php echo $t['title']; ?></h1>
            <p style="opacity: 0.5; margin-top: 0.5rem;"><?php echo ($lang=='tr' ? 'Gelen bildirimleri buradan kontrol edebilirsin.' : 'Manage incoming video reports.'); ?></p>
        </div>
        <div style="display: flex; gap: 1rem;">
            <a href="report_types.php" class="btn btn-primary" style="background: rgba(255,255,255,0.05); color: #fff;">
                <i class="fas fa-list"></i> <?php echo $t['report_types']; ?>
            </a>
            <a href="../index.php" target="_blank" class="btn btn-primary">
                <i class="fas fa-external-link-alt"></i> <?php echo $t['view_site']; ?>
            </a>
        </div>
    </header>

    <div class="admin-tabs">
        <a href="?tab=pending" class="admin-tab <?php echo $current_tab == 'pending' ? 'active' : ''; ?>"><?php echo $t['tab_pending']; ?></a>
        <a href="?tab=reviewed" class="admin-tab <?php echo $current_tab == 'reviewed' ? 'active' : ''; ?>"><?php echo $t['tab_reviewed']; ?></a>
    </div>

    <?php if(empty($reports)): ?>
        <p style="opacity: 0.3; text-align: center; margin-top: 5rem; font-size: 1.2rem;"><?php echo $t['no_reports']; ?></p>
    <?php else: ?>
        <table class="report-table">
            <thead>
                <tr>
                    <th><?php echo $t['video_title']; ?></th>
                    <th><?php echo $t['reason']; ?></th>
                    <th>User</th>
                    <th><?php echo $t['status']; ?></th>
                    <th><?php echo $t['date']; ?></th>
                    <th><?php echo $t['actions']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($reports as $r): ?>
                <?php 
                    $safe_desc = $r['description'] ? htmlspecialchars($r['description']) : $t['no_desc'];
                    $report_json = json_encode([
                        'video' => $r['video_name'] ?? 'Deleted Video',
                        'reason' => ($lang == 'tr' ? $r['reason_tr'] : $r['reason_en']),
                        'desc' => $safe_desc,
                        'user' => $r['username'] ?? 'Guest',
                        'date' => date('d.m.Y H:i', strtotime($r['created_at'])),
                        'status' => $t[$r['status']]
                    ]);
                ?>
                <tr class="report-row" onclick='showReportDetails(<?php echo htmlspecialchars($report_json, ENT_QUOTES); ?>)'>
                    <td>
                        <a href="../video.php?id=<?php echo $r['video_id']; ?>" target="_blank" class="video-link" onclick="event.stopPropagation()">
                            <i class="fas fa-play-circle"></i>
                            <div style="font-weight: 700;">
                                <?php echo htmlspecialchars($r['video_name'] ?? 'Deleted Video'); ?>
                                <div style="font-size: 0.7rem; opacity: 0.3;">ID: <?php echo $r['video_id']; ?></div>
                            </div>
                        </a>
                    </td>
                    <td>
                        <i class="fas fa-exclamation-triangle" style="color: var(--primary-red); opacity: 0.4; margin-right: 5px;"></i>
                        <?php echo $lang == 'tr' ? $r['reason_tr'] : $r['reason_en']; ?>
                    </td>
                    <td><?php echo htmlspecialchars($r['username'] ?? 'Guest'); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $r['status']; ?>">
                            <?php echo $t[$r['status']]; ?>
                        </span>
                    </td>
                    <td style="font-size: 0.8rem; opacity: 0.4;"><?php echo date('d.m.Y H:i', strtotime($r['created_at'])); ?></td>
                    <td>
                        <div style="display: flex; gap: 10px;" onclick="event.stopPropagation()">
                            <?php if($r['status'] == 'pending'): ?>
                                <a href="?tab=<?php echo $current_tab; ?>&status=reviewed&id=<?php echo $r['id']; ?>" class="btn-circle" title="<?php echo $t['mark_reviewed']; ?>"><i class="fas fa-check"></i></a>
                            <?php endif; ?>
                            <a href="?tab=<?php echo $current_tab; ?>&delete=<?php echo $r['id']; ?>" class="btn-circle" onclick="return confirm('Silmek istediğinize emin misiniz?')" title="<?php echo $t['delete']; ?>"><i class="fas fa-trash"></i></a>
                            <a href="videos.php?edit=<?php echo $r['video_id']; ?>" class="btn-circle" title="Edit Video"><i class="fas fa-video"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<!-- Details Dialog -->
<div class="dialog-overlay" id="report-dialog-overlay" onclick="closeReportDetails()">
    <div class="dialog-card" id="report-dialog" onclick="event.stopPropagation()">
        <h2 style="margin-bottom: 2.5rem; font-size: 2rem; font-weight: 800;"><?php echo $t['details_title']; ?></h2>
        
        <div class="detail-row">
            <label><?php echo $t['video_title']; ?></label>
            <span id="det-video"></span>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="detail-row">
                <label><?php echo $t['reason']; ?></label>
                <span id="det-reason"></span>
            </div>
            <div class="detail-row">
                <label>User</label>
                <span id="det-user"></span>
            </div>
        </div>

        <div class="detail-row">
            <label><?php echo $t['description']; ?></label>
            <div class="detail-desc" id="det-desc"></div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="detail-row">
                <label><?php echo $t['status']; ?></label>
                <span id="det-status"></span>
            </div>
            <div class="detail-row">
                <label><?php echo $t['date']; ?></label>
                <span id="det-date"></span>
            </div>
        </div>

        <button class="btn btn-primary" style="width: 100%; margin-top: 1rem;" onclick="closeReportDetails()">Kapat</button>
    </div>
</div>

<script>
function showReportDetails(data) {
    document.getElementById('det-video').innerText = data.video;
    document.getElementById('det-reason').innerText = data.reason;
    document.getElementById('det-user').innerText = data.user;
    document.getElementById('det-desc').innerText = data.desc;
    document.getElementById('det-status').innerText = data.status;
    document.getElementById('det-date').innerText = data.date;

    const overlay = document.getElementById('report-dialog-overlay');
    const dialog = document.getElementById('report-dialog');
    overlay.style.display = 'flex';
    setTimeout(() => dialog.classList.add('active'), 10);
}

function closeReportDetails() {
    const dialog = document.getElementById('report-dialog');
    const overlay = document.getElementById('report-dialog-overlay');
    dialog.classList.remove('active');
    setTimeout(() => overlay.style.display = 'none', 300);
}
</script>

</body>
</html>
