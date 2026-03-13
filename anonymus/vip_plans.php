<?php
include 'auth.php';
include '../includes/db.php';

if (!isset($_SESSION['admin_lang'])) {
    $_SESSION['admin_lang'] = 'tr';
}
$lang = $_SESSION['admin_lang'];

$t = [
    'tr' => [
        'title' => 'VIP Plan Yönetimi',
        'add_plan' => 'Yeni Plan Ekle',
        'edit_plan' => 'Planı Düzenle',
        'plan_name_tr' => 'Plan Adı (Türkçe)',
        'plan_name_en' => 'Plan Adı (İngilizce)',
        'price' => 'Fiyat (AZN)',
        'duration' => 'Süre (Gün)',
        'features_tr' => 'Özellikler (Türkçe - Her satıra bir özellik)',
        'features_en' => 'Özellikler (İngilizce - Her satıra bir özellik)',
        'status' => 'Durum',
        'active' => 'Aktif',
        'passive' => 'Pasif',
        'save' => 'Kaydet',
        'actions' => 'İşlemler',
        'delete_confirm' => 'Bu planı silmek istediğinize emin misiniz?',
        'success' => 'İşlem başarıyla tamamlandı.',
        'f_no_ads' => 'Reklamsız Deneyim',
        'f_premium' => 'Premium Videolara Erişim',
        'f_bonus' => 'Hediye Bakiye (AZN)'
    ],
    'en' => [
        'title' => 'VIP Plan Management',
        'add_plan' => 'Add New Plan',
        'edit_plan' => 'Edit Plan',
        'plan_name_tr' => 'Plan Name (Turkish)',
        'plan_name_en' => 'Plan Name (English - Optional)',
        'price' => 'Price (AZN)',
        'duration' => 'Duration (Days)',
        'features_tr' => 'Features Display (Turkish - One per line)',
        'features_en' => 'Features Display (English - One per line)',
        'status' => 'Status',
        'active' => 'Active',
        'passive' => 'Passive',
        'save' => 'Save',
        'actions' => 'Actions',
        'delete_confirm' => 'Are you sure you want to delete this plan?',
        'success' => 'Operation completed successfully.',
        'f_no_ads' => 'No Ads Experience',
        'f_premium' => 'Access to Premium Videos',
        'f_bonus' => 'Bonus Balance (AZN)'
    ]
][$lang];

// Handle Actions (Add/Edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plan_id = isset($_POST['plan_id']) ? (int)$_POST['plan_id'] : 0;
    $name_tr = trim($_POST['name_tr']);
    $name_en = trim($_POST['name_en']);
    if (empty($name_en)) $name_en = $name_tr; // Fallback to TR if EN is empty

    $price = (float)$_POST['price'];
    $duration = (int)$_POST['duration'];
    $feat_tr = trim($_POST['features_tr']);
    $feat_en = trim($_POST['features_en']);
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Functional Features
    $no_ads = isset($_POST['no_ads']) ? 1 : 0;
    $premium_access = isset($_POST['premium_access']) ? 1 : 0;
    $bonus_balance = (float)$_POST['bonus_balance'];

    if ($plan_id > 0) {
        $stmt = $pdo->prepare("UPDATE vip_plans SET name_tr=?, name_en=?, price=?, duration_days=?, features_tr=?, features_en=?, status=?, no_ads=?, premium_access=?, bonus_balance=? WHERE id=?");
        $stmt->execute([$name_tr, $name_en, $price, $duration, $feat_tr, $feat_en, $status, $no_ads, $premium_access, $bonus_balance, $plan_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO vip_plans (name_tr, name_en, price, duration_days, features_tr, features_en, status, no_ads, premium_access, bonus_balance) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name_tr, $name_en, $price, $duration, $feat_tr, $feat_en, $status, $no_ads, $premium_access, $bonus_balance]);
    }
    header("Location: vip_plans.php?msg=success");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM vip_plans WHERE id = ?")->execute([(int)$_GET['delete']]);
    header("Location: vip_plans.php?msg=success");
    exit;
}

$plans = $pdo->query("SELECT * FROM vip_plans ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
$edit_plan = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM vip_plans WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_plan = $stmt->fetch();
}

// Get site settings for logo etc
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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --admin-sidebar: #151515; --admin-content: #1e1e1e; }
        body { background: var(--admin-content); color: white; margin: 0; display: flex; height: 100vh; font-family: 'Outfit', sans-serif; overflow: hidden; }
        
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

        .main-pane { flex: 1; padding: 3rem; overflow-y: auto; background: radial-gradient(circle at top right, rgba(211, 47, 47, 0.03) 0%, rgba(30, 30, 30, 1) 50%); }
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .card { background: rgba(255, 255, 255, 0.02); padding: 2.5rem; border-radius: 25px; border: 1px solid rgba(255, 255, 255, 0.05); margin-bottom: 2rem; }
        .input-group { margin-bottom: 1.5rem; }
        .input-group label { display: block; margin-bottom: 0.8rem; font-size: 0.85rem; font-weight: 700; color: rgba(255,255,255,0.4); text-transform: uppercase; }
        .input-group input, .input-group textarea, .input-group select { width: 100%; padding: 1.1rem; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); border-radius: 18px; color: #fff; outline: none; transition: 0.3s; }
        .input-group input:focus { border-color: var(--primary-red); }
        .btn-submit { background: var(--primary-red); color: white; padding: 1rem 2rem; border-radius: 15px; border: none; font-weight: 800; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(211,47,47,0.3); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 2rem; }
        th { text-align: left; padding: 1.2rem; background: rgba(255,255,255,0.03); color: rgba(255,255,255,0.4); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; }
        td { padding: 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .status-badge { padding: 4px 10px; border-radius: 8px; font-size: 0.7rem; font-weight: 800; }
        .status-active { background: rgba(76, 175, 80, 0.1); color: #4CAF50; }
        .status-passive { background: rgba(244, 67, 54, 0.1); color: #F44336; }
        .actions { display: flex; gap: 10px; }
        .btn-icon { width: 35px; height: 35px; border-radius: 10px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s; }
        .btn-edit { background: rgba(33, 150, 243, 0.1); color: #2196F3; }
        .btn-delete { background: rgba(244, 67, 54, 0.1); color: #F44336; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <main class="main-pane">
        <header class="header-bar">
            <h1><?php echo $t['title']; ?></h1>
        </header>

        <?php if(isset($_GET['msg'])): ?>
            <div class="msg-toast <?php echo $_GET['msg'] == 'error' ? 'error' : ''; ?>">
                <i class="fas <?php echo $_GET['msg'] == 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                <?php 
                    if($_GET['msg'] == 'success') echo $t['success'];
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

        <div class="card">
            <h2 style="margin-top:0;"><?php echo $edit_plan ? $t['edit_plan'] : $t['add_plan']; ?></h2>
            <form method="POST">
                <?php if($edit_plan): ?><input type="hidden" name="plan_id" value="<?php echo $edit_plan['id']; ?>"><?php endif; ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="input-group">
                        <label><?php echo $t['plan_name_tr']; ?></label>
                        <input type="text" name="name_tr" value="<?php echo $edit_plan ? htmlspecialchars($edit_plan['name_tr']) : ''; ?>" required>
                    </div>
                    <div class="input-group">
                        <label><?php echo $t['plan_name_en']; ?></label>
                        <input type="text" name="name_en" value="<?php echo $edit_plan ? htmlspecialchars($edit_plan['name_en']) : ''; ?>">
                    </div>
                </div>
                
                <h3 style="font-size: 1rem; opacity: 0.6; margin: 1rem 0;"><?php echo $lang == 'tr' ? 'Fonksiyonel Özellikler' : 'Functional Features'; ?></h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 15px; margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="no_ads" style="width: auto;" <?php echo (!$edit_plan || $edit_plan['no_ads']) ? 'checked' : ''; ?>>
                        <?php echo $t['f_no_ads']; ?>
                    </label>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="premium_access" style="width: auto;" <?php echo (!$edit_plan || $edit_plan['premium_access']) ? 'checked' : ''; ?>>
                        <?php echo $t['f_premium']; ?>
                    </label>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label style="margin-bottom: 0.3rem; font-size: 0.75rem;"><?php echo $t['f_bonus']; ?></label>
                        <input type="number" step="0.01" name="bonus_balance" value="<?php echo $edit_plan ? $edit_plan['bonus_balance'] : '0.00'; ?>" style="padding: 0.6rem;">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="input-group">
                        <label><?php echo $t['price']; ?></label>
                        <input type="number" step="0.01" name="price" value="<?php echo $edit_plan ? $edit_plan['price'] : ''; ?>" required>
                    </div>
                    <div class="input-group">
                        <label><?php echo $t['duration']; ?></label>
                        <input type="number" name="duration" value="<?php echo $edit_plan ? $edit_plan['duration_days'] : ''; ?>" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="input-group">
                        <label><?php echo $t['features_tr']; ?></label>
                        <textarea name="features_tr" rows="5"><?php echo $edit_plan ? htmlspecialchars($edit_plan['features_tr']) : ''; ?></textarea>
                    </div>
                    <div class="input-group">
                        <label><?php echo $t['features_en']; ?></label>
                        <textarea name="features_en" rows="5"><?php echo $edit_plan ? htmlspecialchars($edit_plan['features_en']) : ''; ?></textarea>
                    </div>
                </div>
                <div class="input-group" style="display: flex; align-items: center; gap: 10px;">
                    <label style="margin-bottom:0; cursor:pointer;">
                        <input type="checkbox" name="status" style="width: auto;" <?php echo (!$edit_plan || $edit_plan['status']) ? 'checked' : ''; ?>>
                        <?php echo $t['active']; ?>
                    </label>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> <?php echo $t['save']; ?></button>
                <?php if($edit_plan): ?>
                    <a href="vip_plans.php" style="color: rgba(255,255,255,0.5); margin-left:1rem; text-decoration:none;">Vazgeç</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Plan Adı</th>
                        <th>Fiyat</th>
                        <th>Süre</th>
                        <th>Durum</th>
                        <th><?php echo $t['actions']; ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $p): ?>
                        <tr>
                            <td>#<?php echo $p['id']; ?></td>
                            <td><?php echo htmlspecialchars($p['name_'.$lang]); ?></td>
                            <td><?php echo number_format($p['price'], 2); ?> AZN</td>
                            <td><?php echo $p['duration_days']; ?> Gün</td>
                            <td>
                                <span class="status-badge <?php echo $p['status'] ? 'status-active' : 'status-passive'; ?>">
                                    <?php echo $p['status'] ? $t['active'] : $t['passive']; ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="?edit=<?php echo $p['id']; ?>" class="btn-icon btn-edit" title="Düzenle"><i class="fas fa-edit"></i></a>
                                <a href="?delete=<?php echo $p['id']; ?>" class="btn-icon btn-delete" title="Sil" onclick="return confirm('<?php echo $t['delete_confirm']; ?>')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
