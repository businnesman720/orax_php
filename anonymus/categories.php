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
        'title' => 'Category Management',
        'tab_title' => 'Manage Categories',
        'add_cat' => 'New Category',
        'edit_cat' => 'Edit Category',
        'save' => 'Save Category',
        'cancel' => 'Cancel',
        'table_name_tr' => 'Name (TR)',
        'table_name_en' => 'Name (EN)',
        'table_slug' => 'Slug',
        'table_actions' => 'Actions',
        'placeholder_tr' => 'Category Name (TR)',
        'placeholder_en' => 'Category Name (EN)',
        'confirm_delete' => 'Delete this category? This might affect videos!',
        'dashboard' => 'Dashboard',
        'videos' => 'Videos',
        'categories' => 'Categories',
        'users' => 'Users',
        'settings' => 'Site Settings',
        'logout' => 'Safe Logout',
        'view_site' => 'Live View'
    ],
    'tr' => [
        'title' => 'Kategori Yönetimi',
        'tab_title' => 'Kategorileri Yönet',
        'add_cat' => 'Yeni Kategori',
        'edit_cat' => 'Kategoriyi Düzenle',
        'save' => 'Kategoriyi Kaydet',
        'cancel' => 'İptal',
        'table_name_tr' => 'Ad (TR)',
        'table_name_en' => 'Ad (EN)',
        'table_slug' => 'Slug',
        'table_actions' => 'İşlemler',
        'placeholder_tr' => 'Kategori Adı (TR)',
        'placeholder_en' => 'Kategori Adı (EN)',
        'confirm_delete' => 'Bu kategoriyi silmek istediğinize emin misiniz? Videolar etkilenebilir!',
        'dashboard' => 'Panel',
        'videos' => 'Videolar',
        'categories' => 'Kategoriler',
        'users' => 'Kullanıcılar',
        'settings' => 'Site Ayarları',
        'logout' => 'Güvenli Çıkış',
        'view_site' => 'Siteyi Gör'
    ]
];
$t = $texts[$lang];

// Silme İşlemi
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: categories.php");
    exit;
}

// Ekleme/Düzenleme İşlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $id = $_POST['id'] ?? null;
    $name_tr = $_POST['name_tr'];
    $name_en = $_POST['name_en'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name_en)));

    if ($id) {
        $stmt = $pdo->prepare("UPDATE categories SET name_tr=?, name_en=?, slug=? WHERE id=?");
        $stmt->execute([$name_tr, $name_en, $slug, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name_tr, name_en, slug) VALUES (?, ?, ?)");
        $stmt->execute([$name_tr, $name_en, $slug]);
    }
    header("Location: categories.php");
    exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();

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

        .btn-add { 
            background: linear-gradient(135deg, var(--primary-red), var(--accent-red)); 
            color: white; padding: 1rem 2rem; border-radius: 15px; border: none; font-weight: 800; 
            cursor: pointer; display: flex; align-items: center; gap: 10px; transition: 0.4s; 
            box-shadow: 0 10px 20px rgba(211, 47, 47, 0.3);
        }
        .btn-add:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(211, 47, 47, 0.5); }

        .cat-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        .cat-table th { padding: 1.2rem; text-align: left; opacity: 0.4; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1.5px; }
        .cat-row { background: rgba(255,255,255,0.02); transition: 0.3s; }
        .cat-row:hover { background: rgba(255,255,255,0.05); }
        .cat-row td { padding: 1.5rem 1.2rem; border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03); }
        .cat-row td:first-child { border-radius: 20px 0 0 20px; border-left: 1px solid rgba(255,255,255,0.03); }
        .cat-row td:last-child { border-radius: 0 20px 20px 0; border-right: 1px solid rgba(255,255,255,0.03); }

        .btn-circle { width: 45px; height: 45px; border-radius: 15px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05); color: #fff; cursor: pointer; border: none; transition: 0.3s; }
        .btn-circle:hover { background: var(--primary-red); transform: scale(1.1); }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; backdrop-filter: blur(20px); align-items: center; justify-content: center; padding: 2rem; }
        .modal-content { background: #1a1a1a; padding: 3rem; border-radius: 35px; width: 100%; max-width: 500px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 30px 60px rgba(0,0,0,0.5); }
        .input-group { margin-bottom: 1.8rem; }
        .input-group label { display: block; margin-bottom: 0.8rem; font-size: 0.85rem; font-weight: 700; color: rgba(255,255,255,0.4); text-transform: uppercase; }
        .input-group input { width: 100%; padding: 1.1rem; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); border-radius: 18px; color: #fff; outline: none; transition: 0.3s; }
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
        <li><a href="categories.php" class="active"><i class="fas fa-folder"></i> <?php echo $t['categories']; ?></a></li>
        <li><a href="users.php"><i class="fas fa-user-friends"></i> <?php echo $t['users']; ?></a></li>
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
            <button class="btn-add" onclick="openModal()">
                <i class="fas fa-plus"></i> <?php echo $t['add_cat']; ?>
            </button>
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

    <table class="cat-table">
        <thead>
            <tr>
                <th>ID</th>
                <th><?php echo $t['table_name_tr']; ?></th>
                <th><?php echo $t['table_name_en']; ?></th>
                <th><?php echo $t['table_slug']; ?></th>
                <th><?php echo $t['table_actions']; ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($categories as $c): ?>
            <tr class="cat-row">
                <td>#<?php echo $c['id']; ?></td>
                <td style="font-weight: 700;"><?php echo $c['name_tr']; ?></td>
                <td><?php echo $c['name_en']; ?></td>
                <td style="opacity: 0.5; font-size: 0.85rem; font-weight: 600;"><?php echo $c['slug']; ?></td>
                <td>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn-circle" onclick='editCat(<?php echo json_encode($c); ?>)'><i class="fas fa-pen"></i></button>
                        <a href="?delete=<?php echo $c['id']; ?>" class="btn-circle" onclick="return confirm('<?php echo $t['confirm_delete']; ?>')"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

<div class="modal" id="catModal">
    <div class="modal-content">
        <h2 id="modalTitle" style="margin-bottom: 2.5rem; font-size: 2rem; font-weight: 800;"><?php echo $t['add_cat']; ?></h2>
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="c_id">
            <div class="input-group">
                <label><?php echo $t['placeholder_tr']; ?></label>
                <input type="text" name="name_tr" id="c_name_tr" required>
            </div>
            <div class="input-group">
                <label><?php echo $t['placeholder_en']; ?></label>
                <input type="text" name="name_en" id="c_name_en" required>
            </div>
            <div style="display:flex; gap: 1.5rem; margin-top: 3rem; justify-content: flex-end;">
                <button type="button" class="btn-add" style="background: rgba(255,255,255,0.05); color: #888; box-shadow: none;" onclick="closeModal()"><?php echo $t['cancel']; ?></button>
                <button type="submit" class="btn-add" style="padding: 1rem 3.5rem;"><?php echo $t['save']; ?></button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('catModal');

    function openModal() {
        document.getElementById('modalTitle').innerText = "<?php echo $t['add_cat']; ?>";
        document.getElementById('c_id').value = "";
        document.querySelector('form').reset();
        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    function editCat(cat) {
        document.getElementById('modalTitle').innerText = "<?php echo $t['edit_cat']; ?>";
        document.getElementById('c_id').value = cat.id;
        document.getElementById('c_name_tr').value = cat.name_tr;
        document.getElementById('c_name_en').value = cat.name_en;
        modal.style.display = 'flex';
    }
</script>

</body>
</html>
