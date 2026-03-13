<?php
$curr = basename($_SERVER['PHP_SELF']);

// Ensure common sidebar translations exist even if the main page missed them
if (!isset($t['dashboard'])) $t['dashboard'] = ($lang == 'tr' ? 'Panel' : 'Dashboard');
if (!isset($t['videos'])) $t['videos'] = ($lang == 'tr' ? 'Videolar' : 'Videos');
if (!isset($t['categories'])) $t['categories'] = ($lang == 'tr' ? 'Kategoriler' : 'Categories');
if (!isset($t['reports'])) $t['reports'] = ($lang == 'tr' ? 'Raporlar' : 'Reports');
if (!isset($t['payment_management'])) $t['payment_management'] = ($lang == 'tr' ? 'Ödeme Yönetimi' : 'Payment Management');
if (!isset($t['users'])) $t['users'] = ($lang == 'tr' ? 'Kullanıcılar' : 'Users');
if (!isset($t['settings'])) $t['settings'] = ($lang == 'tr' ? 'Ayarlar' : 'Settings');
if (!isset($t['ads'])) $t['ads'] = ($lang == 'tr' ? 'Reklam Yönetimi' : 'Ads Management');
if (!isset($t['vip_plans'])) $t['vip_plans'] = ($lang == 'tr' ? 'VIP Planları' : 'VIP Plans');
if (!isset($t['logout'])) $t['logout'] = ($lang == 'tr' ? 'Güvenli Çıkış' : 'Logout');
?>

<aside class="sidebar">
    <?php if(!empty($current_settings['logo']) && file_exists('../' . $current_settings['logo'])): ?>
        <div style="text-align: center; margin-bottom: 4rem;">
            <img src="../<?php echo $current_settings['logo']; ?>" alt="Logo" style="width: <?php echo !empty($current_settings['admin_logo_width']) ? htmlspecialchars($current_settings['admin_logo_width']) : '200px'; ?>; max-width: 100%; object-fit: contain; filter: drop-shadow(0 0 20px rgba(211, 47, 47, 0.3));">
        </div>
    <?php else: ?>
        <div class="sidebar-logo">ORAX</div>
    <?php endif; ?>
    
    <ul class="side-nav">
        <li><a href="dashboard.php" class="<?php echo $curr == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> <?php echo $t['dashboard']; ?></a></li>
        <li><a href="videos.php" class="<?php echo $curr == 'videos.php' ? 'active' : ''; ?>"><i class="fas fa-video"></i> <?php echo $t['videos']; ?></a></li>
        <li><a href="categories.php" class="<?php echo $curr == 'categories.php' ? 'active' : ''; ?>"><i class="fas fa-folder"></i> <?php echo $t['categories']; ?></a></li>
        <li><a href="reports.php" id="nav-reports" class="<?php echo ($curr == 'reports.php' || $curr == 'report_types.php') ? 'active' : ''; ?>"><i class="fas fa-flag"></i> <?php echo $t['reports']; ?></a></li>
        <li><a href="payment_methods.php" id="nav-payments" class="<?php echo $curr == 'payment_methods.php' ? 'active' : ''; ?>"><i class="fas fa-wallet"></i> <?php echo $t['payment_management']; ?></a></li>
        <li><a href="users.php" class="<?php echo $curr == 'users.php' ? 'active' : ''; ?>"><i class="fas fa-user-friends"></i> <?php echo $t['users']; ?></a></li>
        <li><a href="ads.php" class="<?php echo $curr == 'ads.php' ? 'active' : ''; ?>"><i class="fas fa-ad"></i> <?php echo $t['ads']; ?></a></li>
        <li><a href="vip_plans.php" class="<?php echo $curr == 'vip_plans.php' ? 'active' : ''; ?>"><i class="fas fa-gem"></i> <?php echo $t['vip_plans']; ?></a></li>
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

<style>
.side-nav a { position: relative; }
.nav-badge {
    position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
    background: var(--primary-red); color: white; min-width: 20px; height: 20px;
    border-radius: 10px; display: flex; align-items: center; justify-content: center;
    font-size: 0.65rem; font-weight: 900; padding: 0 6px; box-shadow: 0 0 10px rgba(211,47,47,0.5);
}
</style>

<script>
let lastStats = { payments: 0, reports: 0 };
const notifySound = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');

function checkAdminStats() {
    fetch('api/admin_stats.php')
        .then(res => res.json())
        .then(data => {
            updateBadge('nav-payments', data.payments);
            updateBadge('nav-reports', data.reports);

            if (data.payments > lastStats.payments || data.reports > lastStats.reports) {
                notifySound.play().catch(e => console.log("Sound error:", e));
            }
            lastStats = data;
        });
}

function updateBadge(id, count) {
    const el = document.getElementById(id);
    if (!el) return;
    
    let badge = el.querySelector('.nav-badge');
    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'nav-badge';
            el.appendChild(badge);
        }
        badge.innerText = count;
    } else if (badge) {
        badge.remove();
    }
}

setInterval(checkAdminStats, 10000);
checkAdminStats();
</script>
