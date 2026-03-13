<?php
include 'auth.php';
include '../includes/db.php';

// Dil ayarı
if (!isset($_SESSION['admin_lang'])) {
    $_SESSION['admin_lang'] = 'tr';
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
        'reports' => 'Reports',
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other',
        'balance' => 'Balance',
        'edit_user' => 'Edit User',
        'save_changes' => 'Save Changes',
        'delete_confirm' => 'Are you sure you want to delete this user?',
        'saved' => 'User updated successfully!',
        'deleted' => 'User deleted successfully!'
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
        'reports' => 'Raporlar',
        'male' => 'Erkek',
        'female' => 'Kadın',
        'other' => 'Diğer',
        'balance' => 'Bakiye',
        'edit_user' => 'Kullanıcıyı Düzenle',
        'save_changes' => 'Değişiklikleri Kaydet',
        'delete_confirm' => 'Bu kullanıcıyı silmek istediğinize emin misiniz?',
        'saved' => 'Kullanıcı başarıyla güncellendi!',
        'deleted' => 'Kullanıcı başarıyla silindi!'
    ]
];
$t = $texts[$lang];

// Handle Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_user') {
    $id = $_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $balance = trim($_POST['balance']);
    
    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, balance = ? WHERE id = ?");
    if ($stmt->execute([$username, $email, $balance, $id])) {
        header("Location: users.php?msg=success"); exit;
    } else {
        header("Location: users.php?msg=error"); exit;
    }
}

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$id])) {
        header("Location: users.php?msg=deleted"); exit;
    } else {
        header("Location: users.php?msg=error"); exit;
    }
}

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
        .lang-pills-admin a { text-decoration: none; color: white; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; opacity: 0.4; transition: 0.3s; }
        .lang-pills-admin a.active { background: var(--primary-red); opacity: 1; }

        .main-pane {
            flex: 1; padding: 3rem; overflow-y: auto;
            background: radial-gradient(circle at top right, rgba(211, 47, 47, 0.03) 0%, rgba(30, 30, 30, 1) 50%);
        }

        .header-bar {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 4rem;
        }

        .header-bar h1 { font-size: 2.5rem; font-weight: 800; }

        .user-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .user-table th { padding: 1.2rem; text-align: left; opacity: 0.4; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1.5px; }
        .user-row { background: rgba(255,255,255,0.02); transition: 0.3s; cursor: pointer; }
        .user-row:hover { background: rgba(255,255,255,0.05); transform: translateX(5px); }
        .user-row td { padding: 1.5rem 1.2rem; border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03); }
        .user-row td:first-child { border-radius: 20px 0 0 20px; border-left: 1px solid rgba(255,255,255,0.03); }
        .user-row td:last-child { border-radius: 0 20px 20px 0; border-right: 1px solid rgba(255,255,255,0.03); }

        .gender-badge { background: rgba(211, 47, 47, 0.1); color: var(--primary-red); padding: 6px 15px; border-radius: 10px; font-size: 0.8rem; font-weight: 800; }
        .balance-badge { background: rgba(76, 175, 80, 0.1); color: #4caf50; padding: 6px 15px; border-radius: 10px; font-size: 0.85rem; font-weight: 800; }
        
        .btn-circle { width: 45px; height: 45px; border-radius: 15px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.3); border: none; cursor: pointer; transition: 0.3s; }
        .btn-circle:hover { background: var(--primary-red); color: #fff; transform: scale(1.1); }
        
        /* Dialog Styling */
        .dialog-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 2000; display: none; align-items: center; justify-content: center; padding: 2rem; }
        .dialog-card { border-radius: 30px; background: #1a1a1a; width: 100%; max-width: 600px; border: 1px solid rgba(255,255,255,0.05); padding: 3rem; transform: scale(0.9); opacity: 0; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative;}
        .dialog-card.active { transform: scale(1); opacity: 1; }
        
        .input-group { margin-bottom: 1.5rem; }
        .input-group label { display: block; font-size: 0.75rem; opacity: 0.3; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem; font-weight: 800; }
        .input-group input { width: 100%; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); padding: 1.1rem; border-radius: 18px; color: white; outline: none; transition: 0.3s; font-family: inherit; font-size: 1rem; }
        .input-group input:focus { border-color: var(--primary-red); }

        .btn-primary { background: var(--primary-red); color: white; padding: 1rem 2rem; border-radius: 12px; text-decoration: none; border: none; font-weight: 700; transition: 0.3s; cursor: pointer; display: inline-flex; align-items: center; gap: 10px; font-family: inherit; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(211, 47, 47, 0.3); }

        .msg-toast { 
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            background: rgba(76, 175, 80, 0.95); border: 1px solid #4CAF50; color: #fff; 
            padding: 1rem 2rem; border-radius: 12px; font-weight: 600; font-size: 1rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: flex; align-items: center; gap: 10px;
            animation: slideIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275), fadeOut 0.5s ease-in 3.5s forwards;
        }
        .msg-toast.error { background: rgba(211, 47, 47, 0.95); border-color: #D32F2F; }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

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
        <div class="msg-toast <?php echo ($_GET['msg'] == 'error' ? 'error' : ''); ?>">
            <i class="fas <?php echo ($_GET['msg'] == 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'); ?>"></i>
            <?php 
                if($_GET['msg'] == 'success') echo $t['saved'];
                elseif($_GET['msg'] == 'deleted') echo $t['deleted'];
                else echo 'Bir hata oluştu!';
            ?>
        </div>
    <?php endif; ?>

    <div class="user-list">
        <?php if(count($users) > 0): ?>
        <table class="user-table">
            <thead>
                <tr>
                    <th><?php echo $t['table_username']; ?></th>
                    <th><?php echo $t['table_email']; ?></th>
                    <th><?php echo $t['balance']; ?></th>
                    <th><?php echo $t['table_gender']; ?></th>
                    <th><?php echo $t['table_date']; ?></th>
                    <th><?php echo $t['table_actions']; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <?php 
                    $user_json = json_encode([
                        'id' => $u['id'],
                        'username' => $u['username'],
                        'email' => $u['email'],
                        'balance' => $u['balance'],
                        'gender' => $t[$u['gender']] ?? $u['gender']
                    ]);
                ?>
                <tr class="user-row" onclick='openUserModal(<?php echo htmlspecialchars($user_json, ENT_QUOTES); ?>)'>
                    <td><div style="font-weight: 700;"><?php echo htmlspecialchars($u['username']); ?></div><div style="font-size: 0.7rem; opacity: 0.3;">ID: #<?php echo $u['id']; ?></div></td>
                    <td><span style="opacity: 0.6; font-weight: 600;"><?php echo htmlspecialchars($u['email']); ?></span></td>
                    <td><span class="balance-badge"><?php echo number_format($u['balance'], 2); ?> AZN</span></td>
                    <td><span class="gender-badge"><?php echo $t[$u['gender']] ?? $u['gender']; ?></span></td>
                    <td><span style="opacity: 0.4; font-size: 0.85rem; font-weight: 600;"><?php echo date('d.m.Y', strtotime($u['created_at'])); ?></span></td>
                    <td>
                        <div style="display: flex; gap: 10px;" onclick="event.stopPropagation()">
                            <button class="btn-circle" onclick='openUserModal(<?php echo htmlspecialchars($user_json, ENT_QUOTES); ?>)'><i class="fas fa-pen"></i></button>
                            <a href="?delete=<?php echo $u['id']; ?>" class="btn-circle" onclick="return confirm('<?php echo $t['delete_confirm']; ?>')"><i class="fas fa-trash"></i></a>
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

<!-- User Modal -->
<div class="dialog-overlay" id="user-modal-overlay" onclick="closeUserModal()">
    <div class="dialog-card" id="user-modal" onclick="event.stopPropagation()">
        <h2 style="margin-bottom: 2.5rem; font-size: 2rem; font-weight: 800;"><?php echo $t['edit_user']; ?></h2>
        
        <form method="POST">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="edit-user-id">
            
            <div class="input-group">
                <label><?php echo $t['table_username']; ?></label>
                <input type="text" name="username" id="edit-username" required>
            </div>
            
            <div class="input-group">
                <label><?php echo $t['table_email']; ?></label>
                <input type="email" name="email" id="edit-email" required>
            </div>
            
            <div class="input-group">
                <label><?php echo $t['balance']; ?> (AZN)</label>
                <input type="number" step="0.01" name="balance" id="edit-balance" required>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1; justify-content: center;"><?php echo $t['save_changes']; ?></button>
                <button type="button" class="btn btn-primary" style="background: rgba(255,255,255,0.05); color: #888;" onclick="closeUserModal()">Kapat</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUserModal(user) {
    document.getElementById('edit-user-id').value = user.id;
    document.getElementById('edit-username').value = user.username;
    document.getElementById('edit-email').value = user.email;
    document.getElementById('edit-balance').value = user.balance;

    const overlay = document.getElementById('user-modal-overlay');
    const dialog = document.getElementById('user-modal');
    overlay.style.display = 'flex';
    setTimeout(() => dialog.classList.add('active'), 10);
}

function closeUserModal() {
    const dialog = document.getElementById('user-modal');
    const overlay = document.getElementById('user-modal-overlay');
    dialog.classList.remove('active');
    setTimeout(() => overlay.style.display = 'none', 300);
}

// URL cleanup
if (window.location.search.includes('msg=')) {
    setTimeout(() => {
        const url = new URL(window.location);
        url.searchParams.delete('msg');
        window.history.replaceState({}, document.title, url);
    }, 4000);
}
</script>

</body>
</html>
