<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirection if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

include 'includes/header.php';
include 'includes/db.php';

$msg = '';
$msg_type = 'success';

// Handle Purchase
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['plan_id'])) {
    $plan_id = (int)$_POST['plan_id'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT * FROM vip_plans WHERE id = ? AND status = 1");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch();

    if ($plan) {
        if ($user_balance >= $plan['price']) {
            // Calculate new expiry
            $current_expire = ($is_vip && $vip_expire) ? strtotime($vip_expire) : time();
            $new_expire = date('Y-m-d H:i:s', strtotime("+{$plan['duration_days']} days", $current_expire));

            try {
                $pdo->beginTransaction();

                // Apply Plan Features
                $bonus = (float)$plan['bonus_balance'];
                $no_ads = (int)$plan['no_ads'];
                $prem_access = (int)$plan['premium_access'];

                // Deduct balance and Update User
                $pdo->prepare("UPDATE users SET balance = balance - ? + ?, is_vip = 1, vip_expire = ?, vip_no_ads = ?, vip_premium_access = ? WHERE id = ?")
                    ->execute([$plan['price'], $bonus, $new_expire, $no_ads, $prem_access, $user_id]);

                // Log subscription
                $pdo->prepare("INSERT INTO vip_subscriptions (user_id, plan_id, price, start_date, end_date) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$user_id, $plan_id, $plan['price'], date('Y-m-d H:i:s'), $new_expire]);

                $pdo->commit();
                $msg = ($lang == 'tr' ? 'Tebrikler! Premium üyeliğiniz başarıyla aktif edildi. ' . ($bonus > 0 ? "+$bonus AZN hediye bakiyeniz tanımlandı!" : "") : 'Congratulations! Your Premium membership has been activated. ' . ($bonus > 0 ? "+$bonus AZN bonus balance added!" : ""));
                $is_vip = true;
                $vip_expire = $new_expire;
                $user_balance = ($user_balance - $plan['price']) + $bonus;
            } catch (Exception $e) {
                $pdo->rollBack();
                $msg = "Error: " . $e->getMessage();
                $msg_type = 'error';
            }
        } else {
            $msg = ($lang == 'tr' ? 'Yetersiz bakiye! Lütfen cüzdanınıza para yükleyin.' : 'Insufficient balance! Please top up your wallet.');
            $msg_type = 'error';
        }
    }
}

$plans = $pdo->query("SELECT * FROM vip_plans WHERE status = 1 ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);

$t_titles = [
    'tr' => ['title' => 'Orax Premium', 'subtitle' => 'Sınırsız Eğlence, Sıfır Reklam!', 'active_vip' => 'VIP Üyeliğiniz Aktif', 'expires' => 'Bitiş Tarihi:'],
    'en' => ['title' => 'Orax Premium', 'subtitle' => 'Unlimited Fun, Zero Ads!', 'active_vip' => 'Your VIP Membership is Active', 'expires' => 'Expires on:']
][$lang];
?>

<div class="container" style="padding: 4rem 10px; max-width: 1200px; margin: 0 auto;">
    
    <div style="text-align: center; margin-bottom: 4rem;">
        <h1 style="font-size: 3.5rem; font-weight: 950; margin-bottom: 1rem; background: linear-gradient(135deg, #FFD700, #FFA500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 0 30px rgba(255,215,0,0.3));">
            <?php echo $t_titles['title']; ?>
        </h1>
        <p style="font-size: 1.2rem; opacity: 0.6;"><?php echo $t_titles['subtitle']; ?></p>
    </div>

    <?php if($msg): ?>
        <div style="background: <?php echo $msg_type == 'success' ? 'rgba(76, 175, 80, 0.1)' : 'rgba(244, 67, 54, 0.1)'; ?>; border: 1px solid <?php echo $msg_type == 'success' ? '#4CAF50' : '#F44336'; ?>; color: #fff; padding: 1.5rem; border-radius: 20px; text-align: center; margin-bottom: 3rem; animation: slideDown 0.5s ease;">
            <i class="fas <?php echo $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <?php if($is_vip): ?>
        <div class="card" style="background: rgba(255,215,0,0.05); border: 2px dashed rgba(255,215,0,0.3); padding: 2rem; border-radius: 25px; text-align: center; margin-bottom: 4rem;">
            <h2 style="color: #FFD700; margin-top: 0;"><i class="fas fa-crown"></i> <?php echo $t_titles['active_vip']; ?></h2>
            <p style="margin-bottom: 0; opacity: 0.8;"><?php echo $t_titles['expires']; ?> <strong><?php echo date('d.m.Y H:i', strtotime($vip_expire)); ?></strong></p>
        </div>
    <?php endif; ?>

    <div class="plans-grid">
        <?php foreach ($plans as $plan): ?>
            <div class="plan-card <?php echo $plan['price'] > 50 ? 'featured' : ''; ?>">
                <?php if($plan['price'] > 50): ?><div class="popular-badge">POPÜLER</div><?php endif; ?>
                <div class="plan-header">
                    <h3><?php echo htmlspecialchars($plan['name_'.$lang]); ?></h3>
                    <div class="price">
                        <span class="currency">AZN</span>
                        <?php echo number_format($plan['price'], 2); ?>
                    </div>
                    <div class="duration"><?php echo $plan['duration_days']; ?> <?php echo ($lang == 'tr' ? 'Günlük VIP' : 'Days VIP'); ?></div>
                </div>
                
                <ul class="features-list">
                    <?php 
                    $features = explode("\n", $plan['features_'.$lang]);
                    foreach ($features as $f): if(empty(trim($f))) continue; 
                    ?>
                        <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars(trim($f)); ?></li>
                    <?php endforeach; ?>
                </ul>

                <form method="POST">
                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                    <button type="submit" class="btn-buy" <?php echo $user_balance < $plan['price'] ? 'title="'.$t['insufficient_balance'].'"' : ''; ?>>
                        <i class="fas fa-gem"></i> <?php echo ($lang == 'tr' ? 'Hemen Al' : 'Buy Now'); ?>
                    </button>
                    <?php if($user_balance < $plan['price']): ?>
                        <div style="margin-top: 10px; font-size: 0.75rem; color: #F44336;"><?php echo ($lang == 'tr' ? 'Yetersiz bakiye' : 'Insufficient balance'); ?></div>
                    <?php endif; ?>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2.5rem;
}

.plan-card {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 30px;
    padding: 3rem;
    text-align: center;
    transition: 0.4s;
    position: relative;
    display: flex; flex-direction: column;
}

.plan-card:hover { transform: translateY(-15px); background: rgba(255,255,255,0.04); border-color: rgba(255,215,0,0.3); }

.plan-card.featured {
    border-color: #FFD700;
    background: rgba(255,215,0,0.02);
    transform: scale(1.05);
}
.plan-card.featured:hover { transform: translateY(-15px) scale(1.05); }

.popular-badge {
    position: absolute; top: -15px; left: 50%; transform: translateX(-50%);
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #000; padding: 5px 20px; border-radius: 50px; font-weight: 800; font-size: 0.75rem;
}

.plan-header h3 { font-size: 1.8rem; font-weight: 900; margin-bottom: 1.5rem; }
.plan-header .price { font-size: 3rem; font-weight: 950; margin-bottom: 0.5rem; }
.plan-header .currency { font-size: 1rem; vertical-align: super; opacity: 0.5; margin-right: 5px; }
.plan-header .duration { font-size: 0.9rem; font-weight: 600; color: #FFD700; margin-bottom: 2rem; }

.features-list { list-style: none; padding: 0; text-align: left; margin-bottom: 3rem; flex: 1; }
.features-list li { margin-bottom: 1.2rem; display: flex; align-items: start; gap: 10px; font-size: 0.95rem; opacity: 0.8; }
.features-list i { color: #4CAF50; margin-top: 3px; }

.btn-buy {
    width: 100%; padding: 1.2rem; border-radius: 18px; border: none;
    background: #fff; color: #000; font-weight: 800; font-size: 1.1rem;
    cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;
}
.plan-card.featured .btn-buy { background: linear-gradient(135deg, #FFD700, #FFA500); }
.btn-buy:hover { transform: scale(1.05); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
.btn-buy:disabled { opacity: 0.2; cursor: not-allowed; }

@keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

@media (max-width: 768px) {
    .plan-card { padding: 2rem; }
}
</style>

<?php include 'includes/footer.php'; ?>
