<?php
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Kullanıcı bilgilerini tekrar çek
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Oynatma listelerini çek
$stmt = $pdo->prepare("SELECT * FROM playlists WHERE user_id = ?");
$stmt->execute([$user_id]);
$playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <button class="add-balance-btn" title="<?php echo ($lang == 'tr' ? 'Bakiye Yükle' : 'Add Balance'); ?>" onclick="openBalanceDialog()">
                    <i class="fas fa-plus"></i>
                </button>
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
    const settingsBtn = document.getElementById('settings-trigger-btn');

    if (view === 'settings') {
        mainView.style.display = 'none';
        settingsView.style.display = 'block';
        settingsBtn.style.opacity = '0';
        settingsBtn.style.pointerEvents = 'none';
        updateThemeBtnText();
    } else {
        mainView.style.display = 'block';
        settingsView.style.display = 'none';
        settingsBtn.style.opacity = '1';
        settingsBtn.style.pointerEvents = 'auto';
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
