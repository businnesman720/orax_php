<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}
include 'includes/header.php';
include_once 'includes/db.php';

$user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini tekrar çek
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Oynatma listelerini çek
$stmt = $pdo->prepare("SELECT * FROM playlists WHERE user_id = ?");
$stmt->execute([$user_id]);
$playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ödeme geçmişini çek
$stmt = $pdo->prepare("SELECT pr.*, pm.name_tr as method_name_tr, pm.name_en as method_name_en, pm.image_path as method_image 
                       FROM payment_requests pr 
                       LEFT JOIN payment_methods pm ON pr.method_id = pm.id 
                       WHERE pr.user_id = ? 
                       ORDER BY pr.created_at DESC");
$stmt->execute([$user_id]);
$payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Okunmamış değişim sayısını bul (status pending değil ve is_seen 0)
// Öncelikle exists kontrolü yapalım (veritabanı güncelliği için)
try {
    $pdo->query("SELECT is_seen FROM payment_requests LIMIT 1");
} catch(Exception $e) {
    $pdo->exec("ALTER TABLE payment_requests ADD COLUMN is_seen TINYINT(1) DEFAULT 0");
}

$stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM payment_requests WHERE user_id = ? AND status != 'pending' AND is_seen = 0");
$stmt->execute([$user_id]);
$unread_status_count = $stmt->fetch()['unread_count'];

// AJAX ile okundu olarak işaretleme
if (isset($_GET['mark_seen'])) {
    $pdo->prepare("UPDATE payment_requests SET is_seen = 1 WHERE user_id = ? AND status != 'pending'")->execute([$user_id]);
    echo "ok"; exit;
}
?>

<div class="container animate-fade">
    <div class="profile-header" style="margin-bottom: 3rem; text-align: center;">
        <div style="position: relative; display: inline-block;">
            <div class="user-avatar-custom-large">
                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
            </div>
            <button onclick="toggleProfileView('settings')" class="profile-settings-btn" id="settings-trigger-btn" title="<?php echo ($lang == 'tr' ? 'Ayarlar' : 'Settings'); ?>">
                <i class="fas fa-cog"></i>
            </button>
        </div>
        <h1 style="font-size: 2.5rem; margin: 1rem 0;"><?php echo htmlspecialchars($user['username']); ?></h1>
        <div class="profile-stats">
            <div class="stat-item">
                <i class="fas fa-wallet"></i>
                <div class="stat-info">
                    <span><?php echo ($lang == 'tr' ? 'Bakiyeniz' : 'Balance'); ?></span>
                    <strong><?php echo number_format($user['balance'], 2); ?> AZN</strong>
                </div>
                <div style="display: flex; gap: 5px; margin-left: auto;">
                    <div style="position: relative;">
                        <button class="add-balance-btn" title="<?php echo ($lang == 'tr' ? 'Geçmiş' : 'History'); ?>" onclick="toggleProfileView('history')" style="background: rgba(255,255,255,0.05); box-shadow: none;">
                            <i class="fas fa-history" style="color: white !important;"></i>
                        </button>
                        <?php if ($unread_status_count > 0): ?>
                            <span id="history-badge" style="position: absolute; top: -5px; right: -5px; background: #d32f2f; color: #fff; width: 22px; height: 22px; border-radius: 50%; font-size: 0.7rem; font-weight: 900; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 10px rgba(211,47,47,0.5); border: 2px solid #141414;">
                                <?php echo $unread_status_count; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <button class="add-balance-btn" title="<?php echo ($lang == 'tr' ? 'Bakiye Yükle' : 'Add Balance'); ?>" onclick="location.href='deposit.php'">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="stat-item">
                <i class="fas fa-list-ul"></i>
                <div class="stat-info">
                    <span><?php echo ($lang == 'tr' ? 'Oynatma Listeleri' : 'Playlists'); ?></span>
                    <strong><?php echo count($playlists); ?></strong>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-content">
        <!-- Profile View -->
        <div id="profile-main-view" class="animate-fade">
            <div class="profile-section">
                <h2 class="section-title"><i class="fas fa-list-ul"></i> <?php echo ($lang == 'tr' ? 'Oynatma Listelerim' : 'My Playlists'); ?></h2>
                <div class="playlist-grid">
                    <?php if (count($playlists) > 0): ?>
                        <?php foreach($playlists as $pl): ?>
                            <a href="playlist.php?id=<?php echo $pl['id']; ?>" class="profile-playlist-card">
                                <div class="playlist-icon">
                                    <i class="fas fa-folder"></i>
                                </div>
                                <div class="playlist-info">
                                    <h3><?php echo htmlspecialchars($pl['name']); ?></h3>
                                    <p><?php echo date('d.m.Y', strtotime($pl['created_at'])); ?></p>
                                </div>
                                <i class="fas fa-chevron-right arrow"></i>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="opacity: 0.5; padding: 2rem; border: 1px dashed rgba(255,255,255,0.1); border-radius: 20px; text-align: center;">
                            <?php echo ($lang == 'tr' ? 'Henüz bir oynatma listeniz yok.' : 'You don\'t have any playlists yet.'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-actions" style="margin-top: 4rem; display: flex; gap: 1rem; justify-content: center;">
                <a href="logout.php" class="btn btn-primary" style="padding: 1rem 2.5rem; border-radius: 50px; font-weight: 800;">
                    <i class="fas fa-sign-out-alt"></i> <?php echo ($lang == 'tr' ? 'Güvenli Çıkış' : 'Logout'); ?>
                </a>
            </div>
        </div>

        <!-- Payment History View -->
        <div id="profile-history-view" class="animate-fade" style="display: none;">
            <div class="profile-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                    <h2 class="section-title" style="margin-bottom: 0;"><i class="fas fa-history"></i> <?php echo ($lang == 'tr' ? 'Ödeme Geçmişim' : 'My Payment History'); ?></h2>
                    <div class="payment-filters" style="display: flex; gap: 5px; background: rgba(0,0,0,0.2); padding: 5px; border-radius: 12px;">
                        <button onclick="filterPayments('all')" class="filter-btn active" id="f-all"><?php echo ($lang == 'tr' ? 'Hepsi' : 'All'); ?></button>
                        <button onclick="filterPayments('pending')" class="filter-btn" id="f-pending"><?php echo ($lang == 'tr' ? 'Beklemede' : 'Pending'); ?></button>
                        <button onclick="filterPayments('approved')" class="filter-btn" id="f-approved"><?php echo ($lang == 'tr' ? 'Onaylandı' : 'Approved'); ?></button>
                        <button onclick="filterPayments('rejected')" class="filter-btn" id="f-rejected"><?php echo ($lang == 'tr' ? 'Reddedildi' : 'Rejected'); ?></button>
                    </div>
                    <button onclick="toggleProfileView('main')" class="btn-dialog-cancel" style="border: none; background: rgba(255,255,255,0.05); padding: 0.6rem 1.2rem; border-radius: 50px; color: white; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-arrow-left"></i> <?php echo ($lang == 'tr' ? 'Geri Dön' : 'Go Back'); ?>
                    </button>
                </div>

                <div class="payment-history-list">
                    <?php if (count($payment_history) > 0): ?>
                        <?php foreach($payment_history as $pay): 
                            $status_info = [
                                'pending' => ['tr' => 'Beklemede', 'en' => 'Pending', 'color' => '#ffc107', 'bg' => 'rgba(255,193,7,0.1)'],
                                'approved' => ['tr' => 'Onaylandı', 'en' => 'Approved', 'color' => '#4caf50', 'bg' => 'rgba(76,175,80,0.1)'],
                                'rejected' => ['tr' => 'Reddedildi', 'en' => 'Rejected', 'color' => 'var(--primary-red)', 'bg' => 'rgba(211,47,47,0.1)']
                            ][$pay['status']];
                            $m_name = ($lang == 'tr' ? $pay['method_name_tr'] : ($pay['method_name_en'] ?? $pay['method_name_tr'])) ?? ($lang == 'tr' ? 'Bilinmeyen Yöntem' : 'Unknown Method');
                        ?>
                            <div class="payment-card" data-status="<?php echo $pay['status']; ?>">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
                                    <div class="pay-method-info">
                                        <div class="pay-icon">
                                            <?php if($pay['method_image']): ?>
                                                <img src="<?php echo $pay['method_image']; ?>" style="width: 100%; height: 100%; border-radius: 8px; object-fit: cover;">
                                            <?php else: ?>
                                                <i class="fas fa-wallet"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="pay-details">
                                            <h3><?php echo htmlspecialchars($m_name); ?></h3>
                                            <p><?php echo date('d.m.Y H:i', strtotime($pay['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <div class="pay-value">
                                        <div style="font-weight: 900; font-size: 1.1rem; color: #fff; margin-bottom: 5px;"><?php echo number_format($pay['amount'], 2); ?> AZN</div>
                                        <div class="status-chip" style="background: <?php echo $status_info['bg']; ?>; color: <?php echo $status_info['color']; ?>;">
                                            <?php echo $status_info[$lang]; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!empty($pay['admin_note'])): ?>
                                    <div style="margin-top: 15px; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 12px; border-left: 3px solid <?php echo $status_info['color']; ?>; width: 100%;">
                                        <label style="display:block; font-size: 0.65rem; opacity: 0.4; font-weight: 900; text-transform: uppercase; margin-bottom: 3px; letter-spacing: 0.5px;">
                                            <?php echo ($lang == 'tr' ? 'Yönetici Notu' : 'Admin Note'); ?>
                                        </label>
                                        <div style="font-size: 0.85rem; opacity: 0.8; line-height: 1.4; font-weight: 600;"><?php echo htmlspecialchars($pay['admin_note']); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="opacity: 0.5; padding: 2rem; border: 1px dashed rgba(255,255,255,0.1); border-radius: 20px; text-align: center;">
                            <?php echo ($lang == 'tr' ? 'Henüz bir ödeme işleminiz bulunmuyor.' : 'You don\'t have any payment transactions yet.'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            </div>
        </div>

        <!-- Settings View -->
        <div id="profile-settings-view" class="animate-fade" style="display: none;">
            <div class="profile-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 class="section-title" style="margin-bottom: 0;"><i class="fas fa-cog"></i> <?php echo ($lang == 'tr' ? 'Uygulama Ayarları' : 'App Settings'); ?></h2>
                    <button onclick="toggleProfileView('main')" class="btn-dialog-cancel" style="border: none; background: rgba(255,255,255,0.05); padding: 0.6rem 1.2rem; border-radius: 50px; color: white; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-arrow-left"></i> <?php echo ($lang == 'tr' ? 'Geri Dön' : 'Go Back'); ?>
                    </button>
                </div>

                <div class="settings-list">
                    <!-- Theme -->
                    <div class="settings-item">
                        <div class="item-meta">
                            <div class="item-icon"><i class="fas fa-adjust"></i></div>
                            <div class="item-text">
                                <h3><?php echo ($lang == 'tr' ? 'Görünüm Modu' : 'Appearance Mode'); ?></h3>
                                <p><?php echo ($lang == 'tr' ? 'Koyu ve açık tema arasında geçiş yapın' : 'Switch between dark and light themes'); ?></p>
                            </div>
                        </div>
                        <div class="toggle-switch-wrapper">
                            <label class="toggle-switch">
                                <input type="checkbox" id="theme-checkbox-list" onchange="toggleThemeGlobal()">
                                <span class="slider"></span>
                            </label>
                            <span id="theme-btn-text-list" style="font-size: 0.85rem; font-weight: 700; min-width: 80px; text-align: right;">...</span>
                        </div>
                    </div>

                    <!-- Language Dropdown -->
                    <div class="settings-item">
                        <div class="item-meta">
                            <div class="item-icon"><i class="fas fa-globe"></i></div>
                            <div class="item-text">
                                <h3><?php echo ($lang == 'tr' ? 'Uygulama Dili' : 'Application Language'); ?></h3>
                                <p><?php echo ($lang == 'tr' ? 'Tercih ettiğiniz dili seçin' : 'Select your preferred language'); ?></p>
                            </div>
                        </div>
                        <div class="custom-select-wrapper">
                            <select onchange="window.location.href='?lang='+this.value" class="settings-select">
                                <option value="tr" <?php echo $lang == 'tr' ? 'selected' : ''; ?>>Türkçe (TR)</option>
                                <option value="en" <?php echo $lang == 'en' ? 'selected' : ''; ?>>English (EN)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Future Settings Placeholder -->
                    <div class="settings-item" style="opacity: 0.5;">
                        <div class="item-meta">
                            <div class="item-icon"><i class="fas fa-bell"></i></div>
                            <div class="item-text">
                                <h3><?php echo ($lang == 'tr' ? 'Bildirimler' : 'Notifications'); ?></h3>
                                <p><?php echo ($lang == 'tr' ? 'Yakında eklenecek' : 'Coming soon'); ?></p>
                            </div>
                        </div>
                        <div class="status-badge">Soon</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-avatar-large { font-size: 8rem; color: var(--primary-red); filter: drop-shadow(0 0 20px rgba(211,47,47,0.2)); }
    .profile-settings-btn { position: absolute; bottom: 5px; right: 5px; background: var(--primary-red); border: none; color: white; width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 15px rgba(211,47,47,0.4); border: 3px solid #0f0f0f; }
    .profile-settings-btn:hover { transform: rotate(45deg) scale(1.1); background: #ff3d3d; }

    .stat-info strong { font-size: 1.2rem; }
    .profile-stats { display: flex; gap: 2rem; justify-content: center; margin-top: 2rem; }
    .stat-item { background: rgba(255,255,255,0.05); padding: 1.2rem 2.5rem; border-radius: 25px; border: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 1rem; }
    .stat-item i { font-size: 1.5rem; color: var(--primary-red); }
    .stat-info { display: flex; flex-direction: column; text-align: left; }
    .stat-info span { font-size: 0.75rem; opacity: 0.5; text-transform: uppercase; font-weight: 800; }

    .add-balance-btn {
        background: var(--primary-red);
        color: white !important;
        border: none;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.3s;
        margin-left: auto;
        box-shadow: 0 4px 15px rgba(211,47,47,0.4);
        font-size: 1.1rem;
        padding: 0;
    }
    .add-balance-btn i {
        color: white !important;
        display: block !important;
        visibility: visible !important;
    }
    .add-balance-btn:hover {
        transform: scale(1.1) rotate(90deg);
        background: #ff3d3d;
        box-shadow: 0 6px 20px rgba(211,47,47,0.6);
    }

    /* Settings View Styles */
    .settings-list { display: flex; flex-direction: column; gap: 0.8rem; }
    .settings-item { background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: space-between; transition: 0.3s; }
    .settings-item:hover { background: rgba(255,255,255,0.06); }
    .item-meta { display: flex; align-items: center; gap: 1.5rem; }
    .item-icon { width: 45px; height: 45px; background: rgba(211,47,47,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary-red); font-size: 1.2rem; }
    .item-text h3 { font-size: 1rem; margin-bottom: 2px; }
    .item-text p { font-size: 0.8rem; opacity: 0.4; }
    
    .settings-select { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); color: white; padding: 0.6rem 1.2rem; border-radius: 12px; font-weight: 600; outline: none; cursor: pointer; transition: 0.3s; }
    .settings-select:hover { border-color: var(--primary-red); }
    .settings-select option { background: #1a1a1a; color: white; }
    
    .status-badge { background: rgba(255,255,255,0.05); padding: 4px 12px; border-radius: 50px; font-size: 0.7rem; font-weight: 800; opacity: 0.5; }
    
    /* Premium Toggle Switch */
    .toggle-switch-wrapper { display: flex; align-items: center; gap: 12px; }
    .toggle-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255,255,255,0.1); transition: .4s; border-radius: 34px; border: 1px solid rgba(255,255,255,0.05); }
    .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.3); }
    input:checked + .slider { background-color: var(--primary-red); border-color: transparent; }
    input:checked + .slider:before { transform: translateX(24px); }

    /* Light Mode Adjustments for Settings */
    body.light-mode .settings-item { background: rgba(0,0,0,0.03); border-color: rgba(0,0,0,0.05); color: #333; }
    body.light-mode .settings-item:hover { background: rgba(0,0,0,0.05); }
    body.light-mode .item-text p { color: #666; opacity: 1; }
    body.light-mode .slider { background-color: rgba(0,0,0,0.1); border-color: rgba(0,0,0,0.1); }
    body.light-mode .settings-select { background: white; border-color: #ddd; color: #333; }
    body.light-mode .btn-dialog-cancel { background: rgba(0,0,0,0.05); color: #333; }
    body.light-mode .section-title { color: #111; }

    .section-title { font-size: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px; }
    .section-title i { color: var(--primary-red); }
    
    .playlist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
    .profile-playlist-card { background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 1rem; text-decoration: none; color: inherit; transition: 0.3s; }
    .profile-playlist-card:hover { background: rgba(255,255,255,0.06); transform: translateX(5px); border-color: var(--primary-red); }
    .playlist-icon { width: 50px; height: 50px; background: rgba(211,47,47,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary-red); font-size: 1.2rem; }
    .playlist-info { flex: 1; }
    .playlist-info h3 { font-size: 1rem; margin-bottom: 2px; }
    .playlist-info p { font-size: 0.8rem; opacity: 0.4; }
    .arrow { opacity: 0.3; }

    /* Payment History Styling */
    .payment-history-list { display: flex; flex-direction: column; gap: 1rem; }
    .payment-card { background: rgba(255,255,255,0.025); border: 1px solid rgba(255,255,255,0.05); padding: 1.2rem 1.5rem; border-radius: 20px; display: flex; align-items: center; justify-content: space-between; transition: 0.3s; }
    .payment-card:hover { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.1); }
    .pay-method-info { display: flex; align-items: center; gap: 1.2rem; }
    .pay-icon { width: 45px; height: 45px; background: rgba(255,255,255,0.05); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.3); font-size: 1.2rem; flex-shrink: 0; }
    .pay-details h3 { font-size: 1rem; font-weight: 800; margin-bottom: 2px; }
    .pay-details p { font-size: 0.75rem; opacity: 0.4; font-weight: 600; }
    .pay-value { text-align: right; }
    .status-chip { padding: 4px 12px; border-radius: 8px; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
    .payment-card { flex-direction: column; align-items: flex-start; }

    .filter-btn { background: none; border: none; color: #fff; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; opacity: 0.4; transition: 0.3s; }
    .filter-btn:hover { opacity: 0.7; }
    .filter-btn.active { background: var(--primary-red); opacity: 1; }

    body.light-mode .payment-card { background: rgba(0,0,0,0.02); border-color: rgba(0,0,0,0.05); }
    body.light-mode .pay-details p { color: #333; opacity: 0.5; }
    body.light-mode .pay-icon { background: rgba(0,0,0,0.05); color: #333; }
    
    @media (max-width: 768px) {
        .profile-stats { flex-direction: column; gap: 1rem; padding: 0 1rem; }
        .stat-item { justify-content: flex-start; }
        .playlist-grid { grid-template-columns: 1fr; }
    }
</style>

<script>
function toggleProfileView(view) {
    const mainView = document.getElementById('profile-main-view');
    const settingsView = document.getElementById('profile-settings-view');
    const historyView = document.getElementById('profile-history-view');
    const settingsBtn = document.getElementById('settings-trigger-btn');

    // Reset all
    mainView.style.display = 'none';
    settingsView.style.display = 'none';
    historyView.style.display = 'none';
    settingsBtn.style.opacity = '1';
    settingsBtn.style.pointerEvents = 'auto';

    if (view === 'settings') {
        settingsView.style.display = 'block';
        settingsBtn.style.opacity = '0';
        settingsBtn.style.pointerEvents = 'none';
        updateThemeBtnText();
    } else if (view === 'history') {
        historyView.style.display = 'block';
        settingsBtn.style.opacity = '0';
        settingsBtn.style.pointerEvents = 'none';
        
        // Mark as seen when opening history
        fetch('profile.php?mark_seen=1').then(() => {
            const badge = document.getElementById('history-badge');
            if (badge) badge.style.display = 'none';
        });
    } else {
        mainView.style.display = 'block';
    }
}

function updateThemeBtnText() {
    const btnText = document.getElementById('theme-btn-text-list');
    const checkbox = document.getElementById('theme-checkbox-list');
    if(!btnText) return;
    const isLight = document.body.classList.contains('light-mode');
    
    // Switch konumunu güncelle
    if(checkbox) checkbox.checked = !isLight;

    btnText.innerText = isLight ? "<?php echo ($lang == 'tr' ? 'Gündüz' : 'Light'); ?>" : "<?php echo ($lang == 'tr' ? 'Gece' : 'Dark'); ?>";
}

function toggleThemeGlobal() {
    const mainToggle = document.getElementById('theme-toggle');
    if (mainToggle) {
        mainToggle.click();
        // updateThemeBtnText otomatik tetiklenmezse diye kısa gecikmeyle çağır
        setTimeout(updateThemeBtnText, 100);
    }
}

function filterPayments(status) {
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById('f-' + status).classList.add('active');
    
    document.querySelectorAll('.payment-card').forEach(card => {
        if (status === 'all' || card.getAttribute('data-status') === status) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function openBalanceDialog() {
    const overlay = document.getElementById('balance-dialog-overlay');
    const dialog = document.getElementById('balance-dialog');
    overlay.style.display = 'flex';
    setTimeout(() => dialog.classList.add('active'), 10);
}

function closeBalanceDialog() {
    const overlay = document.getElementById('balance-dialog-overlay');
    const dialog = document.getElementById('balance-dialog');
    dialog.classList.remove('active');
    setTimeout(() => overlay.style.display = 'none', 300);
}
</script>

<!-- Balance Recharge Dialog -->
<div class="orax-dialog-overlay" id="balance-dialog-overlay">
    <div class="orax-dialog" id="balance-dialog" style="max-width: 450px;">
        <div class="dialog-header">
            <i class="fas fa-wallet"></i>
            <span><?php echo ($lang == 'tr' ? 'Bakiye Yükle' : 'Add Balance'); ?></span>
        </div>
        <div class="dialog-body">
            <p style="margin-bottom: 2rem; opacity: 0.6; font-size: 0.9rem;">
                <?php echo ($lang == 'tr' ? 'Lütfen yüklemek istediğiniz bakiye miktarını ve ödeme yöntemini seçin.' : 'Please select the balance amount and payment method.'); ?>
            </p>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 2rem;">
                <button class="settings-select" style="padding: 1rem 0; width: 100%; cursor: pointer;">10 AZN</button>
                <button class="settings-select" style="padding: 1rem 0; width: 100%; cursor: pointer;">25 AZN</button>
                <button class="settings-select" style="padding: 1rem 0; width: 100%; cursor: pointer;">50 AZN</button>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.8rem; margin-bottom: 2.5rem;">
                <div class="settings-item" style="cursor: pointer; padding: 1rem; margin-bottom: 0;">
                    <div class="item-meta">
                        <div class="item-icon" style="width: 35px; height: 35px;"><i class="fab fa-cc-visa" style="font-size: 1rem;"></i></div>
                        <div class="item-text"><h3 style="font-size: 0.9rem;">Banka Kartı</h3></div>
                    </div>
                    <i class="fas fa-chevron-right" style="opacity: 0.2;"></i>
                </div>
                <div class="settings-item" style="cursor: pointer; padding: 1rem; margin-bottom: 0;">
                    <div class="item-meta">
                        <div class="item-icon" style="width: 35px; height: 35px;"><i class="fab fa-ethereum" style="font-size: 1rem;"></i></div>
                        <div class="item-text"><h3 style="font-size: 0.9rem;">Kripto Ödeme</h3></div>
                    </div>
                    <i class="fas fa-chevron-right" style="opacity: 0.2;"></i>
                </div>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button onclick="closeBalanceDialog()" class="btn" style="flex: 1; background: rgba(255,255,255,0.05); color: white; border-radius: 12px; font-weight: 700;">
                    <?php echo ($lang == 'tr' ? 'İptal' : 'Cancel'); ?>
                </button>
                <button class="btn btn-primary" style="flex: 2; border-radius: 12px; font-weight: 800;" onclick="closeBalanceDialog()">
                    <?php echo ($lang == 'tr' ? 'Devam Et' : 'Continue'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
