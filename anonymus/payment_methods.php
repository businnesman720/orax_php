<?php
include 'auth.php';
include '../includes/db.php';

if (!isset($_SESSION['admin_lang'])) { $_SESSION['admin_lang'] = 'tr'; }
if (isset($_GET['lang'])) {
    $_SESSION['admin_lang'] = $_GET['lang'] == 'tr' ? 'tr' : 'en';
    header("Location: payment_methods.php"); exit;
}
$lang = $_SESSION['admin_lang'];

$texts = [
    'en' => [
        'title' => 'Payment Management',
        'tab_requests' => 'Requests',
        'tab_methods' => 'Methods',
        'add_new' => 'Add New Method',
        'user' => 'User',
        'method' => 'Method',
        'amount' => 'Amount',
        'status' => 'Status',
        'date' => 'Date',
        'actions' => 'Actions',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'no_requests' => 'No requests found.',
        'details' => 'View Data',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save Method',
        'name_tr' => 'Name (TR)',
        'name_en' => 'Name (EN)',
        'instructions_tr' => 'Instructions (TR)',
        'instructions_en' => 'Instructions (EN)',
        'acc_details_tr' => 'Account Details (TR)',
        'acc_details_en' => 'Account Details (EN)',
        'image' => 'Method Logo',
        'image_url' => 'or Image URL',
        'field_label' => 'Field Title',
        'field_type' => 'Field Type',
        'field_meta' => 'Detail (Placeholder)',
        'field_required' => 'Required?',
        'add_field' => 'Add New Field',
        'manage_fields' => 'Manage Fields',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'type_text' => 'Text Output',
        'type_number' => 'Number Input',
        'type_file' => 'Image (Receipt)',
        'type_redirect' => 'Redirect Button',
        'type_info' => 'Info Text',
        'logout' => 'Logout',
        'dashboard' => 'Dashboard',
        'videos' => 'Videos',
        'categories' => 'Categories',
        'users' => 'Users',
        'settings' => 'Settings',
        'reports' => 'Reports',
        'payment_management' => 'Payment Management'
    ],
    'tr' => [
        'title' => 'Ödeme Merkezi',
        'tab_requests' => 'Talepler',
        'tab_methods' => 'Yöntemler',
        'add_new' => 'Yeni Yöntem Ekle',
        'user' => 'Kullanıcı',
        'method' => 'Yöntem',
        'amount' => 'Miktar',
        'status' => 'Durum',
        'date' => 'Tarih',
        'actions' => 'İşlemler',
        'approve' => 'Onayla',
        'reject' => 'Reddet',
        'pending' => 'Bekliyor',
        'approved' => 'Onaylandı',
        'rejected' => 'Reddedildi',
        'no_requests' => 'Talep bulunamadı.',
        'details' => 'Bilgileri Gör',
        'edit' => 'Düzenle',
        'delete' => 'Sil',
        'save' => 'Yöntemi Kaydet',
        'name_tr' => 'Adı (TR)',
        'name_en' => 'Adı (EN)',
        'instructions_tr' => 'Talimatlar (TR)',
        'instructions_en' => 'Talimatlar (EN)',
        'acc_details_tr' => 'Hesap Bilgileri (TR)',
        'acc_details_en' => 'Hesap Bilgileri (EN)',
        'image' => 'Yöntem Logosu',
        'image_url' => 'veya Resim URL',
        'field_label' => 'Alan Başlığı',
        'field_type' => 'Alan Türü',
        'field_meta' => 'Detay (Placeholder)',
        'field_required' => 'Mecburidir?',
        'add_field' => 'Yeni Alan Ekle',
        'manage_fields' => 'Alanları İdare Et',
        'active' => 'Aktif',
        'inactive' => 'Pasif',
        'type_text' => 'Yazı Girişi',
        'type_number' => 'Sayı Girişi',
        'type_file' => 'Fayl Yükleme',
        'type_redirect' => 'Yönlendirme Butonu',
        'type_info' => 'Bilgi Yazısı',
        'logout' => 'Çıkış',
        'dashboard' => 'Panel',
        'videos' => 'Videolar',
        'categories' => 'Kategoriler',
        'users' => 'Kullanıcılar',
        'settings' => 'Ayarlar',
        'reports' => 'Raporlar',
        'payment_management' => 'Ödeme Yönetimi'
    ]
];
$t = $texts[$lang];

$current_tab = $_GET['tab'] ?? 'requests';

if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $pdo->prepare("UPDATE payment_methods SET status = NOT status WHERE id = ?")->execute([$_GET['id']]);
    header("Location: payment_methods.php?tab=methods"); exit;
}

if (isset($_POST['update_request_status']) && $current_tab == 'requests') {
    $req_id = $_POST['req_id'];
    $action = $_POST['action']; // approve or reject
    $note = htmlspecialchars($_POST['admin_note'] ?? '');
    
    $req = $pdo->prepare("SELECT * FROM payment_requests WHERE id = ?");
    $req->execute([$req_id]);
    $request = $req->fetch();
    
    if ($request && $request['status'] == 'pending') {
        if ($action == 'approve') {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("UPDATE payment_requests SET status = 'approved', admin_note = ? WHERE id = ?")->execute([$note, $req_id]);
                $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$request['amount'], $request['user_id']]);
                $pdo->commit();
            } catch (Exception $e) { $pdo->rollBack(); header("Location: payment_methods.php?msg=error"); exit; }
        } else {
            $pdo->prepare("UPDATE payment_requests SET status = 'rejected', admin_note = ? WHERE id = ?")->execute([$note, $req_id]);
        }
    }
    header("Location: payment_methods.php?tab=requests&msg=success"); exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_method'])) {
    $id = $_POST['id'] ?? null;
    $name_tr = $_POST['name_tr'];
    $name_en = !empty($_POST['name_en']) ? $_POST['name_en'] : $name_tr;
    $ins_tr = $_POST['instructions_tr'];
    $ins_en = !empty($_POST['instructions_en']) ? $_POST['instructions_en'] : $ins_tr;
    $acc_tr = $_POST['account_details_tr'] ?? '';
    $acc_en = !empty($_POST['account_details_en']) ? $_POST['account_details_en'] : $acc_tr;

    $image_path = $_POST['existing_image'] ?? '';
    if (!empty($_POST['image_url'])) { $image_path = $_POST['image_url']; }
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'pay_' . time() . '.' . $ext;
        if (!is_dir('../uploads/payments')) mkdir('../uploads/payments', 0777, true);
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/payments/' . $filename);
        $image_path = 'uploads/payments/' . $filename;
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE payment_methods SET name_tr = ?, name_en = ?, instructions_tr = ?, instructions_en = ?, account_details_tr = ?, account_details_en = ?, image_path = ? WHERE id = ?");
        $stmt->execute([$name_tr, $name_en, $ins_tr, $ins_en, $acc_tr, $acc_en, $image_path, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO payment_methods (name_tr, name_en, instructions_tr, instructions_en, account_details_tr, account_details_en, image_path, fields) VALUES (?, ?, ?, ?, ?, ?, ?, '[]')");
        $stmt->execute([$name_tr, $name_en, $ins_tr, $ins_en, $acc_tr, $acc_en, $image_path]);
    }
    header("Location: payment_methods.php?tab=methods&msg=success"); exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_fields'])) {
    $id = $_POST['method_id'];
    $fields = $_POST['fields_json'];
    $pdo->prepare("UPDATE payment_methods SET fields = ? WHERE id = ?")->execute([$fields, $id]);
    header("Location: payment_methods.php?tab=methods&msg=fields_updated"); exit;
}

if (isset($_GET['delete_method'])) {
    $pdo->prepare("DELETE FROM payment_methods WHERE id = ?")->execute([$_GET['delete_method']]);
    header("Location: payment_methods.php?tab=methods"); exit;
}

$status_filter = $_GET['status'] ?? 'all';
$query_parts = ["SELECT pr.*, u.username, pm.name_tr as method_name FROM payment_requests pr JOIN users u ON pr.user_id = u.id LEFT JOIN payment_methods pm ON pr.method_id = pm.id"];
$params = [];

if ($status_filter !== 'all') {
    $query_parts[] = "WHERE pr.status = ?";
    $params[] = $status_filter;
}

$query_parts[] = "ORDER BY pr.created_at DESC LIMIT 100";
if ($current_tab == 'requests') {
    $pdo->query("UPDATE payment_requests SET is_admin_seen = 1 WHERE status = 'pending'");
}

$stmt_requests = $pdo->prepare(implode(" ", $query_parts));
$stmt_requests->execute($params);
$requests = $stmt_requests->fetchAll();

$methods = $pdo->query("SELECT * FROM payment_methods ORDER BY id DESC")->fetchAll();

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
        :root { --admin-sidebar: #151515; --admin-content: #1e1e1e; }
        body { background-color: var(--admin-content); margin: 0; padding: 0; display: flex; height: 100vh; overflow: hidden; font-family: 'Outfit', sans-serif; color: white; }
        .sidebar { width: 300px; background: var(--admin-sidebar); border-right: 1px solid rgba(255, 255, 255, 0.05); display: flex; flex-direction: column; padding: 2.5rem; transition: 0.4s; z-index: 100; }
        .sidebar-logo { font-size: 2.5rem; font-weight: 950; color: var(--primary-red); letter-spacing: 5px; margin-bottom: 4rem; text-shadow: 0 0 20px rgba(211, 47, 47, 0.3); text-align: center; }
        .side-nav { list-style: none; padding: 0; flex: 1; }
        .side-nav li { margin-bottom: 1rem; }
        .side-nav a { display: flex; align-items: center; gap: 1.2rem; padding: 1.2rem 1.5rem; text-decoration: none; color: white; opacity: 0.6; border-radius: 15px; font-weight: 600; transition: 0.4s; border: 1px solid transparent; }
        .side-nav a:hover, .side-nav a.active { opacity: 1; background: rgba(211, 47, 47, 0.08); color: var(--primary-red); border-color: rgba(211, 47, 47, 0.2); transform: translateX(5px); }
        .lang-pills-admin { display: flex; gap: 0.5rem; background: rgba(0,0,0,0.3); padding: 0.3rem; border-radius: 50px; }
        .lang-pills-admin a { text-decoration: none; color: white; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; opacity: 0.4; transition: 0.3s; }
        .lang-pills-admin a.active { background: var(--primary-red); opacity: 1; }
        
        .main-pane { flex: 1; padding: 3rem; overflow-y: auto; background: radial-gradient(circle at top right, rgba(211, 47, 47, 0.03) 0%, rgba(30, 30, 30, 1) 50%); }
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        
        .admin-tabs { display: flex; gap: 1rem; margin-bottom: 3rem; background: rgba(0,0,0,0.2); padding: 0.5rem; border-radius: 20px; width: fit-content; }
        .admin-tab { padding: 0.8rem 2.5rem; border-radius: 15px; text-decoration: none; color: white; opacity: 0.5; font-weight: 700; transition: 0.3s; }
        .admin-tab.active { opacity: 1; background: var(--primary-red); }

        .btn-primary { background: var(--primary-red); color: white; padding: 1rem 2rem; border-radius: 12px; text-decoration: none; border: none; font-weight: 700; transition: 0.3s; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(211, 47, 47, 0.3); }
        .btn-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05); color: #fff; border: none; cursor: pointer; text-decoration: none; }
        .btn-circle:hover { background: var(--primary-red); }

        .premium-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .premium-row { background: rgba(255,255,255,0.02); transition: 0.3s; cursor: pointer; }
        .premium-row:hover { background: rgba(255,255,255,0.05); }
        .premium-row td { padding: 1.5rem 1rem; border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03); }
        .premium-row td:first-child { border-radius: 15px 0 0 15px; border-left: 1px solid rgba(255,255,255,0.03); }
        .premium-row td:last-child { border-radius: 0 15px 15px 0; border-right: 1px solid rgba(255,255,255,0.03); }

        .status-badge { padding: 5px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
        .status-pending { background: rgba(255, 152, 0, 0.1); color: #FF9800; }
        .status-approved { background: rgba(76, 175, 80, 0.1); color: #4CAF50; }
        .status-rejected { background: rgba(211, 47, 47, 0.1); color: #D32F2F; }
        .status-active { background: rgba(76, 175, 80, 0.1); color: #4CAF50; }
        .status-inactive { background: rgba(211, 47, 47, 0.1); color: #D32F2F; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 2000; display: none; align-items: center; justify-content: center; }
        .modal-card { background: #1a1a1a; width: 90%; max-width: 600px; max-height: 90vh; border-radius: 30px; border: 1px solid rgba(255,255,255,0.05); padding: 3rem; overflow-y: auto; transform: scale(0.9); opacity: 0; transition: 0.3s; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
        .modal-card.active { transform: scale(1); opacity: 1; }
        .modal-card.wide { max-width: 900px; }
        
        .input-group { margin-bottom: 1.5rem; }
        .input-group label { display: block; margin-bottom: 0.8rem; font-size: 0.75rem; opacity: 0.4; font-weight: 800; text-transform: uppercase; }
        input, textarea, select { width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); padding: 1.1rem; border-radius: 18px; color: white; outline: none; transition: 0.3s; appearance: none; box-sizing: border-box; }
        select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 1rem center; background-size: 1.2rem; cursor: pointer; }
        input:focus, textarea:focus, select:focus { border-color: var(--primary-red); }

        .btn-manage-fields { background: rgba(211, 47, 47, 0.1); color: var(--primary-red); border: 1px solid rgba(211, 47, 47, 0.2); padding: 8px 15px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-manage-fields:hover { background: var(--primary-red); color: white; }

        .fields-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        .fields-table th { text-align: left; padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.7rem; opacity: 0.4; }
        .fields-table td { padding: 1.2rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.02); }

        .checkbox-group { display: flex; align-items: center; gap: 10px; background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 15px; }
        .checkbox-group input { width: 20px; height: 20px; cursor: pointer; accent-color: var(--primary-red); appearance: auto; }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="main-pane">
    <header class="header-bar">
        <div>
            <h1><?php echo $t['title']; ?></h1>
            <p style="opacity: 0.5; font-size: 0.9rem; margin-top: 5px;">İstifadəçilərin depozit edə biləcəyi hesabları idarə edin.</p>
        </div>
        <button onclick="addNewMethod()" class="btn-primary">
            <i class="fas fa-plus"></i> <?php echo $t['add_new']; ?>
        </button>
    </header>

    <div class="admin-tabs" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <div style="display: flex; gap: 1rem; background: rgba(0,0,0,0.2); padding: 0.5rem; border-radius: 20px;">
            <a href="?tab=requests" class="admin-tab <?php echo $current_tab == 'requests' ? 'active' : ''; ?>"><?php echo $t['tab_requests']; ?></a>
            <a href="?tab=methods" class="admin-tab <?php echo $current_tab == 'methods' ? 'active' : ''; ?>"><?php echo $t['tab_methods']; ?></a>
        </div>
        
        <?php if($current_tab == 'requests'): ?>
            <div class="status-filters-admin" style="display: flex; gap: 5px; background: rgba(0,0,0,0.3); padding: 5px; border-radius: 15px;">
                <a href="?tab=requests&status=all" class="status-filt-item <?php echo $status_filter == 'all' ? 'active':''; ?>">Hepsi</a>
                <a href="?tab=requests&status=pending" class="status-filt-item <?php echo $status_filter == 'pending' ? 'active':''; ?>">Bekleyen</a>
                <a href="?tab=requests&status=approved" class="status-filt-item <?php echo $status_filter == 'approved' ? 'active':''; ?>">Onaylanan</a>
                <a href="?tab=requests&status=rejected" class="status-filt-item <?php echo $status_filter == 'rejected' ? 'active':''; ?>">Reddedilen</a>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .status-filt-item { text-decoration: none; color: #fff; padding: 0.5rem 1.2rem; border-radius: 12px; font-size: 0.7rem; font-weight: 700; opacity: 0.5; transition: 0.3s; }
        .status-filt-item:hover { opacity: 0.8; }
        .status-filt-item.active { background: var(--primary-red); opacity: 1; }
    </style>

    <?php if($current_tab == 'requests'): ?>
        <table class="premium-table">
            <thead>
                <tr style="text-align: left; opacity: 0.4; font-size: 0.8rem; text-transform: uppercase;">
                    <th style="padding: 10px;"><?php echo $t['user']; ?></th>
                    <th><?php echo $t['method']; ?></th>
                    <th><?php echo $t['amount']; ?></th>
                    <th><?php echo $t['status']; ?></th>
                    <th><?php echo $t['date']; ?></th>
                    <th style="text-align: right;"><?php echo $t['actions']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($requests as $r): ?>
                    <tr class="premium-row" onclick='showDetails(<?php echo htmlspecialchars($r['user_data'] ?? '{}', ENT_QUOTES); ?>, <?php echo $r['id']; ?>, "<?php echo $r['status']; ?>", "<?php echo htmlspecialchars($r['admin_note'] ?? '', ENT_QUOTES); ?>")'>
                        <td style="font-weight: 800;"><?php echo htmlspecialchars($r['username']); ?></td>
                        <td style="font-weight: 600;"><?php echo htmlspecialchars($r['method_name'] ?? 'Manual'); ?></td>
                        <td style="color:#4caf50; font-weight: 950; font-size: 1.1rem;"><?php echo number_format($r['amount'], 2); ?> AZN</td>
                        <td><span class="status-badge status-<?php echo $r['status']; ?>"><?php echo $t[$r['status']]; ?></span></td>
                        <td style="opacity:0.4; font-size: 0.8rem;"><?php echo date('d.m.Y H:i', strtotime($r['created_at'])); ?></td>
                        <td style="text-align: right;">
                            <button class="btn-primary" style="padding: 0.5rem 1rem; font-size: 0.75rem;"><i class="fas fa-eye"></i> Detay</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php else: ?>
        <table class="premium-table">
            <thead>
                <tr style="text-align: left; opacity: 0.4; font-size: 0.75rem; text-transform: uppercase;">
                    <th style="padding: 10px; width: 60px;">LOGO</th>
                    <th>AD</th>
                    <th>HESAB DETALLARI</th>
                    <th>TƏLİMAT</th>
                    <th>XÜSUSİ ALANLAR</th>
                    <th>DURUM</th>
                    <th style="text-align: right;">ƏMƏLİYYAT</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($methods as $m): ?>
                    <tr class="premium-row">
                        <td>
                            <div style="width: 45px; height: 45px; background: #fff; border-radius: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <img src="<?php echo filter_var($m['image_path'], FILTER_VALIDATE_URL) ? $m['image_path'] : '../' . $m['image_path']; ?>" style="width: 80%; height: 80%; object-fit: contain;">
                            </div>
                        </td>
                        <td style="font-weight: 800;"><?php echo htmlspecialchars($m['name_tr']); ?></td>
                        <td style="opacity: 0.7;"><?php echo htmlspecialchars($m['account_details_tr']); ?></td>
                        <td style="opacity: 0.5; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($m['instructions_tr']); ?></td>
                        <td>
                            <button onclick='manageFields(<?php echo htmlspecialchars(json_encode($m), ENT_QUOTES, "UTF-8"); ?>)' class="btn-manage-fields">
                                <i class="fas fa-cog"></i> <?php echo $t['manage_fields']; ?>
                            </button>
                        </td>
                        <td>
                            <a href="?toggle_status=1&id=<?php echo $m['id']; ?>" style="text-decoration: none;">
                                <span class="status-badge status-<?php echo $m['status'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $m['status'] ? $t['active'] : $t['inactive']; ?>
                                </span>
                            </a>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                <button onclick='editMethod(<?php echo htmlspecialchars(json_encode($m), ENT_QUOTES, "UTF-8"); ?>)' class="btn-circle"><i class="fas fa-pencil-alt"></i></button>
                                <a href="?delete_method=<?php echo $m['id']; ?>" class="btn-circle" style="color:var(--primary-red);" onclick="return confirm('Silmek istediğine emin misin?')"><i class="fas fa-trash-alt"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>

<!-- Method Modal -->
<div class="modal-overlay" id="method-modal">
    <div class="modal-card" id="method-card">
        <h2 id="modal-title" style="margin-bottom: 2rem;"><?php echo $t['add_new']; ?></h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="m_id">
            <input type="hidden" name="existing_image" id="m_existing_image">
            <input type="hidden" name="save_method" value="1">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div class="input-group">
                    <label><?php echo $t['name_tr']; ?></label>
                    <input type="text" name="name_tr" id="m_name_tr" required placeholder="TR...">
                </div>
                <div class="input-group">
                    <label><?php echo $t['name_en']; ?></label>
                    <input type="text" name="name_en" id="m_name_en" placeholder="EN...">
                </div>
            </div>

            <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 20px; margin-bottom: 1.5rem; border: 1px solid rgba(255,255,255,0.05);">
                <div class="input-group">
                    <label><?php echo $t['image']; ?></label>
                    <input type="file" name="image">
                </div>
                <div class="input-group" style="margin-bottom:0;">
                    <label><?php echo $t['image_url']; ?></label>
                    <input type="text" name="image_url" id="m_image_url" placeholder="https://...">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div class="input-group">
                    <label><?php echo $t['acc_details_tr']; ?></label>
                    <input type="text" name="account_details_tr" id="m_acc_tr" placeholder="TR...">
                </div>
                <div class="input-group">
                    <label><?php echo $t['acc_details_en']; ?></label>
                    <input type="text" name="account_details_en" id="m_acc_en" placeholder="EN...">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div class="input-group">
                    <label><?php echo $t['instructions_tr']; ?></label>
                    <textarea name="instructions_tr" id="m_ins_tr" rows="2" placeholder="TR..."></textarea>
                </div>
                <div class="input-group">
                    <label><?php echo $t['instructions_en']; ?></label>
                    <textarea name="instructions_en" id="m_ins_en" rows="2" placeholder="EN..."></textarea>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn-primary" style="flex:2; justify-content: center;"><?php echo $t['save']; ?></button>
                <button type="button" class="btn-primary" onclick="closeMethodModal()" style="flex:1; justify-content: center; background: rgba(255,255,255,0.05); color: #888;">İptal</button>
            </div>
        </form>
    </div>
</div>

<!-- Manage Fields Modal -->
<div class="modal-overlay" id="fields-modal">
    <div class="modal-card active wide" id="fields-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
            <div>
                <a href="#" style="color: #888; text-decoration: none; font-size: 0.8rem;" onclick="closeFieldsModal()">← Geri</a>
                <h2 style="margin: 5px 0 0 0;">Alanları İdarə Et: <span id="field-method-name" style="color: var(--primary-red);"></span></h2>
                <p style="opacity: 0.5; font-size: 0.8rem; margin-top: 5px;">Bu ödəniş metodu üçün istifadəçidən istənəcək məlumatları təyin edin.</p>
            </div>
            <button onclick="openAddFieldModal()" class="btn-primary">
                <i class="fas fa-plus"></i> Yeni Alan
            </button>
        </div>
        
        <table class="fields-table">
            <thead>
                <tr>
                    <th>SIRA</th>
                    <th>TİP</th>
                    <th>ETİKET (LABEL)</th>
                    <th>MƏCBURİDİR?</th>
                    <th>DETAY (META)</th>
                    <th>ƏMƏLİYYAT</th>
                </tr>
            </thead>
            <tbody id="fields-tbody"></tbody>
        </table>

        <form method="POST" style="margin-top: 2rem;">
            <input type="hidden" name="method_id" id="f_method_id">
            <input type="hidden" name="fields_json" id="f_json_input">
            <input type="hidden" name="save_fields" value="1">
            <button type="submit" class="btn-primary" style="float: right;">Dəyişiklikləri Yadda Saxla</button>
        </form>
    </div>
</div>

<!-- Add New Field Sub-Modal -->
<div class="modal-overlay" id="add-field-modal" style="z-index: 3000;">
    <div class="modal-card active" style="max-width: 450px;">
        <h3>Yeni Alan Əlavə Et</h3>
        
        <div class="input-group">
            <label>Alan Tipi</label>
            <select id="field-type">
                <option value="text">Yazı Girişi (Input Text)</option>
                <option value="number">Rəqəm Girişi (Input Number)</option>
                <option value="file">Fayl Yükleme (Qəbz vs)</option>
                <option value="redirect">Yönləndirmə Butonu</option>
                <option value="info">Sadə Məlumat Yazısı</option>
            </select>
        </div>

        <div class="input-group">
            <label>Etiket (Label)</label>
            <input type="text" id="field-label" placeholder="örn: Kartın son 4 rakamı">
        </div>

        <div class="input-group">
            <label>Detay / Meta (Opsiyonel)</label>
            <input type="text" id="field-meta" placeholder="örn: Link veya Placeholder">
        </div>

        <div class="input-group">
            <div class="checkbox-group">
                <input type="checkbox" id="field-required">
                <label for="field-required">Məcburidir? (İstifadəçi bunu doldurmalıdır)</label>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="button" onclick="saveFieldToTemp()" class="btn-primary" style="flex:1; justify-content: center;">Əlavə Et</button>
            <button type="button" onclick="closeAddFieldModal()" class="btn-primary" style="flex:1; justify-content: center; background: rgba(255,255,255,0.05); color: #888;">İptal</button>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal-overlay" id="detail-overlay" onclick="closeDetails()">
    <div class="modal-card wide active" style="max-width: 550px;" onclick="event.stopPropagation()">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
            <h3 style="margin:0; font-size:1.5rem; font-weight:900;">Talep Detayları</h3>
            <button onclick="closeDetails()" style="background:rgba(255,255,255,0.05); border:none; color:#fff; width:35px; height:35px; border-radius:50%; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        
        <div id="detail-content" style="margin-bottom:2.5rem; max-height:400px; overflow-y:auto; padding-right:10px;"></div>
        
        <div id="detail-footer" style="padding-top:2rem; border-top:1px solid rgba(255,255,255,0.05);">
            <!-- Forms and Notes will be injected here via JS -->
        </div>
    </div>
</div>

<script>
let currentFields = [];

function openMethodModal() {
    document.getElementById('method-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('method-card').classList.add('active'), 10);
}
function closeMethodModal() {
    document.getElementById('method-card').classList.remove('active');
    setTimeout(() => document.getElementById('method-modal').style.display = 'none', 300);
}
function resetMethodForm() {
    document.getElementById('modal-title').innerText = "<?php echo $t['add_new']; ?>";
    document.getElementById('m_id').value = "";
    document.getElementById('m_existing_image').value = "";
    document.getElementById('m_name_tr').value = "";
    document.getElementById('m_name_en').value = "";
    document.getElementById('m_acc_tr').value = "";
    document.getElementById('m_acc_en').value = "";
    document.getElementById('m_ins_tr').value = "";
    document.getElementById('m_ins_en').value = "";
    document.getElementById('m_image_url').value = "";
}

function editMethod(m) {
    resetMethodForm();
    document.getElementById('modal-title').innerText = "<?php echo $t['edit']; ?>: " + m.name_tr;
    document.getElementById('m_id').value = m.id;
    document.getElementById('m_name_tr').value = m.name_tr;
    document.getElementById('m_name_en').value = m.name_en || "";
    document.getElementById('m_acc_tr').value = m.account_details_tr;
    document.getElementById('m_acc_en').value = m.account_details_en || "";
    document.getElementById('m_ins_tr').value = m.instructions_tr;
    document.getElementById('m_ins_en').value = m.instructions_en || "";
    document.getElementById('m_existing_image').value = m.image_path;
    if(m.image_path && (m.image_path.startsWith('http') || m.image_path.startsWith('https'))) {
        document.getElementById('m_image_url').value = m.image_path;
    }
    openMethodModal();
}

function addNewMethod() {
    resetMethodForm();
    openMethodModal();
}

function manageFields(m) {
    document.getElementById('field-method-name').innerText = m.name_tr;
    document.getElementById('f_method_id').value = m.id;
    currentFields = m.fields ? JSON.parse(m.fields) : [];
    renderFieldsTable();
    document.getElementById('fields-modal').style.display = 'flex';
}
function closeFieldsModal() { document.getElementById('fields-modal').style.display = 'none'; }

function openAddFieldModal() { document.getElementById('add-field-modal').style.display = 'flex'; }
function closeAddFieldModal() { document.getElementById('add-field-modal').style.display = 'none'; }

function saveFieldToTemp() {
    const label = document.getElementById('field-label').value;
    if(!label) return alert("Lütfen etiket girin");
    currentFields.push({
        label: label,
        type: document.getElementById('field-type').value,
        meta: document.getElementById('field-meta').value,
        required: document.getElementById('field-required').checked
    });
    renderFieldsTable();
    closeAddFieldModal();
}

function renderFieldsTable() {
    const tbody = document.getElementById('fields-tbody');
    tbody.innerHTML = '';
    currentFields.forEach((f, i) => {
        tbody.innerHTML += `
            <tr>
                <td>${i}</td>
                <td><span style="background:rgba(255,255,255,0.05); padding:4px 8px; border-radius:5px; font-size:0.7rem;">${f.type}</span></td>
                <td style="font-weight:700;">${f.label}</td>
                <td style="color:${f.required ? '#4caf50':'#d32f2f'}; font-weight:800;">${f.required ? 'Bəli':'Xeyr'}</td>
                <td style="opacity:0.5;">${f.meta || '-'}</td>
                <td>
                    <button onclick="removeField(${i})" class="btn-circle" style="color:var(--primary-red);"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
    document.getElementById('f_json_input').value = JSON.stringify(currentFields);
}

function removeField(i) {
    currentFields.splice(i, 1);
    renderFieldsTable();
}

function showDetails(data, reqId, status, existingNote) {
    let html = '';
    for (let key in data) {
        let val = data[key];
        if (typeof val === 'string' && (val.includes('.jpg') || val.includes('.png') || val.includes('.jpeg'))) {
            val = `<br><a href="../${val}" target="_blank"><img src="../${val}" style="max-width:100%; border-radius:15px; margin-top:10px; border:1px solid rgba(255,255,255,0.1); box-shadow: 0 5px 15px rgba(0,0,0,0.5);"></a>`;
        }
        html += `<div style="border-bottom:1px solid rgba(255,255,255,0.05); padding:1.2rem 0;">
                    <span style="opacity:0.4; font-size:0.7rem; text-transform:uppercase; font-weight:900; letter-spacing:1px; display:block; margin-bottom:5px;">${key}</span> 
                    <span style="font-weight:700; font-size:1rem; color:#fff;">${val}</span>
                 </div>`;
    }
    
    document.getElementById('detail-content').innerHTML = html || 'Veri bulunmuyor.';
    
    // Action Form
    const footer = document.getElementById('detail-footer');
    if (status === 'pending') {
        footer.innerHTML = `
            <form method="POST" style="width:100%;">
                <input type="hidden" name="update_request_status" value="1">
                <input type="hidden" name="req_id" value="${reqId}">
                <div class="input-group">
                    <label>YÖNETİCİ NOTU (İsteğe Bağlı)</label>
                    <textarea name="admin_note" placeholder="Red sebebi veya onay mesajı... Balansınıza eklenmiştir gibi." rows="2"></textarea>
                </div>
                <div style="display:flex; gap:1rem; margin-top:1rem;">
                    <button type="submit" name="action" value="approve" class="btn-primary" style="flex:1; background:#4caf50; border:none; color:white; justify-content:center;"><i class="fas fa-check"></i> Onayla</button>
                    <button type="submit" name="action" value="reject" class="btn-primary" style="flex:1; background:var(--primary-red); border:none; color:white; justify-content:center;"><i class="fas fa-times"></i> Reddet</button>
                </div>
            </form>
        `;
    } else {
        footer.innerHTML = `
            <div style="padding:1.5rem; background:rgba(0,0,0,0.2); border-radius:15px; width:100%;">
                <label style="display:block; font-size:0.7rem; opacity:0.4; font-weight:900; margin-bottom:10px; text-transform:uppercase;">YÖNETİCİ NOTU</label>
                <div style="font-weight:600; color:#fff;">${existingNote || '- Not bırakılmadı -'}</div>
            </div>
        `;
    }
    
    document.getElementById('detail-overlay').style.display = 'flex';
}
function closeDetails() { document.getElementById('detail-overlay').style.display = 'none'; }
</script>

</body>
</html>
