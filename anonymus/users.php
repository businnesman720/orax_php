<?php
include 'auth.php';
include '../includes/db.php';

// Dil ayarı (Rule 5: default English)
if (!isset($_SESSION['admin_lang'])) {
    $_SESSION['admin_lang'] = 'en';
}
if (isset($_GET['lang'])) {
    $_SESSION['admin_lang'] = $_GET['lang'] == 'tr' ? 'tr' : 'en';
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}
$lang = $_SESSION['admin_lang'];

$texts = [
    'en' => [
        'users_title' => 'Registered Users',
        'tab_title' => 'Manage Users',
        'table_username' => 'Username',
        'table_email' => 'Email',
        'table_gender' => 'Gender',
        'table_date' => 'Join Date',
        'table_actions' => 'Actions',
        'no_users' => 'No users found.',
        'dashboard' => 'Dashboard',
        'videos' => 'Videos',
        'categories' => 'Categories',
        'users' => 'Users',
        'settings' => 'Site Settings',
        'logout' => 'Safe Logout',
        'view_site' => 'Live View',
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other'
    ],
    'tr' => [
        'users_title' => 'Kayıtlı Kullanıcılar',
        'tab_title' => 'Kullanıcıları Yönet',
        'table_username' => 'Kullanıcı Adı',
        'table_email' => 'E-posta',
        'table_gender' => 'Cinsiyet',
        'table_date' => 'Kayıt Tarihi',
        'table_actions' => 'İşlemler',
        'no_users' => 'Kullanıcı bulunamadı.',
        'dashboard' => 'Panel',
        'videos' => 'Videolar',
        'categories' => 'Kategoriler',
        'users' => 'Kullanıcılar',
        'settings' => 'Site Ayarları',
        'logout' => 'Güvenli Çıkış',
        'view_site' => 'Siteyi Gör',
        'male' => 'Erkek',
        'female' => 'Kadın',
        'other' => 'Diğer'
    ]
];
$t = $texts[$lang];

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

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
    <title>ORAX - <?php echo $t['users']; ?></title>
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
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 4rem;
        }

        .header-bar h1 { font-size: 2.5rem; font-weight: 800; }

        .user-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        .user-table th { padding: 1.2rem; text-align: left; opacity: 0.4; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1.5px; }
        .user-row { background: rgba(255,255,255,0.02); transition: 0.3s; }
        .user-row:hover { background: rgba(255,255,255,0.05); }
        .user-row td { padding: 1.5rem 1.2rem; border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03); }
        .user-row td:first-child { border-radius: 20px 0 0 20px; border-left: 1px solid rgba(255,255,255,0.03); }
        .user-row td:last-child { border-radius: 0 20px 20px 0; border-right: 1px solid rgba(255,255,255,0.03); }

        .gender-badge { background: rgba(211, 47, 47, 0.1); color: var(--primary-red); padding: 6px 15px; border-radius: 10px; font-size: 0.8rem; font-weight: 800; }
        .btn-circle { width: 45px; height: 45px; border-radius: 15px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.3); border: none; cursor: pointer; transition: 0.3s; }
        .btn-circle:hover { background: var(--primary-red); color: #fff; transform: scale(1.1); }
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
        <li><a href="users.php" class="active"><i class="fas fa-user-friends"></i> <?php echo $t['users']; ?></a></li>
        <li><a href="settings.php"><i class="fas fa-cog"></i> <?php echo $t['settings']; ?></a></li>
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
        <h1><?php echo $t['tab_title']; ?></h1>
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

    <div class="user-list">
        <?php if(count($users) > 0): ?>
        <table class="user-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Gender</th>
                    <th>Join Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr class="user-row">
                    <td><div style="font-weight: 700;"><?php echo htmlspecialchars($u['username']); ?></div><div style="font-size: 0.7rem; opacity: 0.3;">ID: #<?php echo $u['id']; ?></div></td>
                    <td><span style="opacity: 0.6; font-weight: 600;"><?php echo htmlspecialchars($u['email']); ?></span></td>
                    <td><span class="gender-badge"><?php echo $t[$u['gender']] ?? $u['gender']; ?></span></td>
                    <td><span style="opacity: 0.4; font-size: 0.85rem; font-weight: 600;"><?php echo date('d.m.Y', strtotime($u['created_at'])); ?></span></td>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <button class="btn-circle"><i class="fas fa-pen"></i></button>
                            <button class="btn-circle"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; opacity: 0.3; padding: 5rem;"><?php echo $t['no_users']; ?></p>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
