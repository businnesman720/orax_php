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
        'title' => 'Report Reasons',
        'add_new' => 'Add New Reason',
        'name_tr' => 'Reason Name (TR)',
        'name_en' => 'Reason Name (EN)',
        'save' => 'Save Reason',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'dashboard' => 'Dashboard',
        'videos' => 'Videos',
        'categories' => 'Categories',
        'users' => 'Users',
        'settings' => 'Settings',
        'reports' => 'Reports',
        'logout' => 'Logout',
        'actions' => 'Actions',
        'success' => 'Saved successfully!'
    ],
    'tr' => [
        'title' => 'Rapor Nedenleri',
        'add_new' => 'Yeni Neden Ekle',
        'name_tr' => 'Neden Adı (TR)',
        'name_en' => 'Neden Adı (EN)',
        'save' => 'Nedeni Kaydet',
        'edit' => 'Düzenle',
        'delete' => 'Sil',
        'dashboard' => 'Panel',
        'videos' => 'Videolar',
        'categories' => 'Kategoriler',
        'users' => 'Kullanıcılar',
        'settings' => 'Ayarlar',
        'reports' => 'Raporlar',
        'logout' => 'Çıkış',
        'actions' => 'İşlemler',
        'success' => 'Başarıyla kaydedildi!'
    ]
];
$t = $texts[$lang];

// Handle POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $name_tr = trim($_POST['name_tr']);
    $name_en = trim($_POST['name_en']);
    
    // Rule: If EN is empty, use TR
    if (empty($name_en)) {
        $name_en = $name_tr;
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE report_types SET name_tr = ?, name_en = ? WHERE id = ?");
        $stmt->execute([$name_tr, $name_en, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO report_types (name_tr, name_en) VALUES (?, ?)");
        $stmt->execute([$name_tr, $name_en]);
    }
    header("Location: report_types.php?msg=success"); exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM report_types WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: report_types.php?msg=success"); exit;
}

$types = $pdo->query("SELECT * FROM report_types ORDER BY id ASC")->fetchAll();

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
        .side-nav a:hover, .side-nav a.active { opacity: 1; background: rgba(211, 47, 47, 0.08); color: var(--primary-red); border-color: rgba(211, 47, 47, 0.2); transform: translateX(5px); }
        .lang-pills-admin { display: flex; gap: 0.5rem; background: rgba(0,0,0,0.3); padding: 0.3rem; border-radius: 50px; }
        .lang-pills-admin a { text-decoration: none; color: white; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; opacity: 0.4; transition: 0.3s; }
        .lang-pills-admin a.active { background: var(--primary-red); opacity: 1; }
        .main-pane { flex: 1; padding: 3rem; overflow-y: auto; background: radial-gradient(circle at top right, rgba(211, 47, 47, 0.03) 0%, rgba(30, 30, 30, 1) 50%); }

        /* Main Area */
        .main-pane {
            flex: 1; padding: 3rem; overflow-y: auto;
            background: radial-gradient(circle at top right, rgba(211, 47, 47, 0.03) 0%, rgba(30, 30, 30, 1) 50%);
        }

        .header-bar {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 4rem;
        }

        .header-bar h1 { font-size: 2.5rem; font-weight: 800; }

        .type-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .type-card { background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); position: relative; transition: 0.3s; }
        .type-card:hover { border-color: var(--primary-red); transform: translateY(-5px); }
        .form-box { background: rgba(255,255,255,0.02); padding: 2.5rem; border-radius: 25px; border: 1px solid rgba(255,255,255,0.05); }
        .input-group { margin-bottom: 1.5rem; }
        .input-group label { display: block; margin-bottom: 0.8rem; font-size: 0.85rem; opacity: 0.5; font-weight: 700; text-transform: uppercase; }
        input { width: 100%; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); padding: 1.1rem; border-radius: 18px; color: white; outline: none; transition: 0.3s; }
        input:focus { border-color: var(--primary-red); }
        .btn-primary { background: var(--primary-red); color: white; padding: 1rem 2rem; border-radius: 12px; text-decoration: none; border: none; font-weight: 700; transition: 0.3s; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(211, 47, 47, 0.3); }
        .btn-delete { color: var(--primary-red); opacity: 0.3; transition: 0.3s; }
        .btn-delete:hover { opacity: 1; }
        .lang-pills-admin { display: flex; gap: 0.5rem; background: rgba(0,0,0,0.3); padding: 0.3rem; border-radius: 50px; }
        .lang-pills-admin a { text-decoration: none; color: white; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; opacity: 0.4; }
        .lang-pills-admin a.active { background: var(--primary-red); opacity: 1; }
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
        <?php 
        $curr = basename($_SERVER['PHP_SELF']); 
        $tab = $_GET['tab'] ?? '';
        ?>
        <li><a href="dashboard.php" class="<?php echo $curr == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> <?php echo $t['dashboard']; ?></a></li>
        <li><a href="videos.php" class="<?php echo $curr == 'videos.php' ? 'active' : ''; ?>"><i class="fas fa-video"></i> <?php echo $t['videos']; ?></a></li>
        <li><a href="categories.php" class="<?php echo $curr == 'categories.php' ? 'active' : ''; ?>"><i class="fas fa-folder"></i> <?php echo $t['categories']; ?></a></li>
        <li><a href="reports.php" class="<?php echo ($curr == 'reports.php' || $curr == 'report_types.php') ? 'active' : ''; ?>"><i class="fas fa-flag"></i> <?php echo $t['reports'] ?? 'Raporlar'; ?></a></li>
        <li><a href="payment_methods.php" class="<?php echo $curr == 'payment_management.php' ? 'active' : ''; ?>"><i class="fas fa-wallet"></i> <?php echo $t['payment_management'] ?? 'Ödeme Yönetimi'; ?></a></li>
        <li><a href="users.php" class="<?php echo $curr == 'users.php' ? 'active' : ''; ?>"><i class="fas fa-user-friends"></i> <?php echo $t['users']; ?></a></li>
        <li><a href="settings.php" class="<?php echo $curr == 'settings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> <?php echo $t['settings']; ?></a></li>
    </ul>

    <div style="margin-top: auto;">
        <div class="lang-pills-admin" style="margin-bottom: 1.5rem;">
            <a href="?lang=tr" class="<?php echo $lang == 'tr' ? 'active' : ''; ?>">TR</a>
            <a href="?lang=en" class="<?php echo $lang == 'en' ? 'active' : ''; ?>">EN</a>
        </div>
        <a href="logout.php" style="text-decoration: none; display: flex; align-items: center; gap: 1.2rem; padding: 1.2rem 1.5rem; background: rgba(255,255,255,0.03); border-radius: 15px; color: #888; width: 100%; font-weight: 600; transition: 0.3s;" onmouseover="this.style.background='rgba(211,47,47,0.1)'; this.style.color='var(--primary-red)';" onmouseout="this.style.background='rgba(255,255,255,0.03)'; this.style.color='#888';">
            <i class="fas fa-power-off"></i> <?php echo $t['logout']; ?>
        </a>
    </div>
</aside>

<main class="main-pane">
    <header class="header-bar">
        <div>
            <h1><?php echo $t['title']; ?></h1>
            <p style="opacity: 0.5; margin-top: 0.5rem;">Hazır rapor nedenlerini buradan yönetebilirsin.</p>
        </div>
        <a href="reports.php" class="btn btn-primary" style="background: rgba(255,255,255,0.1); color:#fff;">
            <i class="fas fa-arrow-left"></i> Geri
        </a>
    </header>

    <div class="type-grid">
        <?php foreach($types as $ty): ?>
            <div class="type-card">
                <div style="font-size: 1.1rem; font-weight: 800; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($ty['name_tr']); ?></div>
                <div style="opacity: 0.4; font-size: 0.8rem;"><?php echo htmlspecialchars($ty['name_en']); ?></div>
                <div style="position: absolute; top: 1.5rem; right: 1.5rem; display: flex; gap: 10px;">
                    <a href="javascript:void(0)" onclick='editType(<?php echo json_encode($ty); ?>)' class="btn-delete" style="color:#2196F3; opacity:0.5;"><i class="fas fa-pen"></i></a>
                    <a href="?delete=<?php echo $ty['id']; ?>" class="btn-delete" onclick="return confirm('Silmek istediğinize emin misiniz?')"><i class="fas fa-trash"></i></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-box">
        <h3 id="form-title" style="margin-bottom: 2rem;"><?php echo $t['add_new']; ?></h3>
        <form method="POST" id="type-form">
            <input type="hidden" name="id" id="type_id">
            <div class="input-group">
                <label><?php echo $t['name_tr']; ?></label>
                <input type="text" name="name_tr" id="t_name_tr" required>
            </div>
            <div class="input-group">
                <label><?php echo $t['name_en']; ?> (Optional)</label>
                <input type="text" name="name_en" id="t_name_en">
            </div>
            <button type="submit" class="btn btn-primary"><?php echo $t['save']; ?></button>
            <button type="button" class="btn btn-primary" onclick="resetForm()" style="background: rgba(255,255,255,0.05); color: #888; margin-left: 10px; display:none;" id="cancel-btn">İptal</button>
        </form>
    </div>
</main>

<script>
function editType(ty) {
    document.getElementById('form-title').innerText = "Nedeni Düzenle";
    document.getElementById('type_id').value = ty.id;
    document.getElementById('t_name_tr').value = ty.name_tr;
    document.getElementById('t_name_en').value = ty.name_en;
    document.getElementById('cancel-btn').style.display = 'inline-flex';
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('form-title').innerText = "<?php echo $t['add_new']; ?>";
    document.getElementById('type_id').value = "";
    document.getElementById('type-form').reset();
    document.getElementById('cancel-btn').style.display = 'none';
}
</script>

</body>
</html>
