<?php
ob_start();
include 'includes/db.php'; // Ensure DB is included for $pdo
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit;
}

$lang = $_SESSION['lang'] ?? 'tr';
$vt = [
    'tr' => [
        'title' => 'Bakiye Yükle',
        'select_method' => 'Ödeme Yöntemi Seçin',
        'instructions' => 'Ödeme Talimatları',
        'acc_details' => 'Hesap Bilgileri',
        'amount' => 'Yüklenecek Miktar (AZN)',
        'submit' => 'Gönder',
        'success' => 'Talebiniz alındı! Onaylandıktan sonra bakiyenize eklenecektir.',
        'error' => 'Bir hata oluştu, lütfen alanları kontrol edin.',
        'fill_fields' => 'Lütfen form detaylarını doldurun',
        'copy' => 'Kopyala',
        'copied' => 'Kopyalandı!',
        'no_method_selected' => 'Lütfen soldan bir ödeme yöntemi seçin'
    ],
    'en' => [
        'title' => 'Add Balance',
        'select_method' => 'Select Payment Method',
        'instructions' => 'Payment Instructions',
        'acc_details' => 'Account Details',
        'amount' => 'Amount to Add (AZN)',
        'submit' => 'Send',
        'success' => 'Request received! It will be added after approval.',
        'error' => 'An error occurred, please check fields.',
        'fill_fields' => 'Please fill in form details',
        'copy' => 'Copy',
        'copied' => 'Copied!',
        'no_method_selected' => 'Please select a payment method from the left'
    ]
][$lang];

// Handle Deposit Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['method_id'])) {
    $method_id = (int)$_POST['method_id'];
    $amount = (float)$_POST['amount'];
    
    $user_data = [];
    foreach ($_POST as $key => $value) {
        if (!in_array($key, ['method_id', 'amount'])) {
            $user_data[$key] = htmlspecialchars($value);
        }
    }

    if (!empty($_FILES)) {
        foreach ($_FILES as $key => $file) {
            if ($file['error'] == 0) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'proof_' . $_SESSION['user_id'] . '_' . time() . '_' . $key . '.' . $ext;
                if (!is_dir('uploads/receipts')) mkdir('uploads/receipts', 0777, true);
                move_uploaded_file($file['tmp_name'], 'uploads/receipts/' . $filename);
                $user_data[$key] = 'uploads/receipts/' . $filename;
            }
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO payment_requests (user_id, method_id, amount, user_data) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION['user_id'], $method_id, $amount, json_encode($user_data)])) {
        header("Location: deposit.php?msg=success"); exit;
    } else {
        header("Location: deposit.php?msg=error"); exit;
    }
}

$methods = $pdo->query("SELECT * FROM payment_methods WHERE status = 1 ORDER BY id ASC")->fetchAll();

include 'includes/header.php';
?>

<div class="deposit-page bg-dark-custom" style="padding: 1.5rem 0; min-height: 100vh; background: #0b0b0b;">
    <div class="container" style="max-width: 1400px;">
        
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert <?php echo $_GET['msg']=='success' ? 'alert-success' : 'alert-danger'; ?> animate-fade" style="border-radius:15px; padding: 1rem 1.5rem; margin-bottom: 2rem; border:none; background: <?php echo $_GET['msg']=='success' ? 'rgba(76,175,80,0.08)' : 'rgba(211,47,47,0.08)'; ?>; color: <?php echo $_GET['msg']=='success' ? '#4caf50' : 'var(--primary-red)'; ?>; font-weight:700; display: flex; align-items: center; gap: 15px; font-size: 0.9rem;">
                <i class="fas <?php echo $_GET['msg']=='success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>" style="font-size: 1.1rem;"></i>
                <?php echo $vt[$_GET['msg']]; ?>
            </div>
        <?php endif; ?>

        <div class="deposit-layout" style="display: flex; gap: 2rem; align-items: flex-start;">
            <!-- Left Side: Header & Methods -->
            <div class="methods-side" style="flex: 0 0 450px;">
                <h1 class="animate-fade" style="font-weight: 900; font-size: 2.2rem; margin-bottom: 2rem; color: #fff; display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-wallet" style="color: var(--primary-red); filter: drop-shadow(0 0 10px rgba(211,47,47,0.3)); font-size: 1.8rem;"></i> 
                    <?php echo $vt['title']; ?>
                </h1>

                <div class="method-selection-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <?php foreach($methods as $m): ?>
                        <div class="method-item" onclick="selectMethod(<?php echo htmlspecialchars(json_encode($m), ENT_QUOTES); ?>)" id="method-<?php echo $m['id']; ?>" style="cursor: pointer; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 25px; text-align:center; transition: 0.3s; position: relative; overflow: hidden;">
                            <div class="active-indicator" style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--primary-red); opacity: 0; transition: 0.3s;"></div>
                            <?php 
                            $img = $m['image_path'];
                            $is_url = filter_var($img, FILTER_VALIDATE_URL);
                            $final_img = $is_url ? $img : $img; 
                            if($img): ?>
                                <img src="<?php echo $final_img; ?>" style="width: 60px; height: 60px; border-radius: 15px; object-fit: cover; margin-bottom: 0.8rem; box-shadow: 0 8px 15px rgba(0,0,0,0.3);">
                            <?php else: ?>
                                <div style="width: 60px; height: 60px; border-radius: 15px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; margin: 0 auto 0.8rem;"><i class="fas fa-university" style="font-size: 1.2rem; opacity: 0.2;"></i></div>
                            <?php endif; ?>
                            <div style="font-weight: 800; font-size: 1rem; color: #fff;"><?php echo $lang == 'tr' ? $m['name_tr'] : ($m['name_en'] ?? $m['name_tr']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Side: Header Shadow & Form -->
            <div class="form-side" style="flex: 1;">
                <!-- Form Box Container -->
                <div id="form-container-placeholder" style="margin-top: 4.2rem;">
                    <!-- Empty State -->
                    <div id="no-method-selected" style="height: 100%; min-height: 450px; background: rgba(255,255,255,0.01); border: 1px dashed rgba(255,255,255,0.05); border-radius: 35px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: rgba(255,255,255,0.15);">
                        <i class="fas fa-shield-alt" style="font-size: 3.5rem; margin-bottom: 1.5rem; opacity: 0.05;"></i>
                        <p style="font-weight: 700; font-size: 1rem; letter-spacing: 0.5px;"><?php echo $vt['no_method_selected']; ?></p>
                    </div>

                    <!-- Form Box -->
                    <div id="deposit-form-box" style="display: none; background: #141414; border: 1px solid rgba(255,255,255,0.05); padding: 2.5rem; border-radius: 35px; color:#fff; box-shadow: 0 20px 40px rgba(0,0,0,0.3);" class="animate-fade">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                            <h3 id="selected-method-name" style="font-weight: 900; margin: 0; font-size: 1.8rem; letter-spacing: -0.5px;"></h3>
                            <button type="button" onclick="showInfoModal()" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); width: 40px; height: 40px; border-radius: 50%; color: #fff; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center;"><i class="fas fa-info" style="font-size: 0.9rem;"></i></button>
                        </div>
                        
                        <div id="acc-details-box" style="background: linear-gradient(135deg, rgba(211,47,47,0.1) 0%, rgba(211,47,47,0.02) 100%); padding: 1.5rem; border-radius: 20px; margin-bottom: 2.5rem; display:none; border: 1px solid rgba(211,47,47,0.12); position: relative; overflow: hidden; align-items: center; justify-content: space-between; gap: 15px;">
                            <div style="position: absolute; top: -10px; right: -10px; font-size: 5rem; color: rgba(211,47,47,0.03); pointer-events: none;"><i class="fas fa-credit-card"></i></div>
                            <div style="flex: 1;">
                                <label style="display:block; font-size: 0.7rem; opacity: 0.5; font-weight: 800; text-transform: uppercase; margin-bottom: 0.3rem; letter-spacing: 1px;"><?php echo $vt['acc_details']; ?></label>
                                <div id="acc-details-text" style="font-weight: 900; font-size: 1.4rem; color: #fff;"></div>
                            </div>
                            <button type="button" onclick="copyAccDetails()" id="btn-copy" style="background: #fff; color: #000; border: none; padding: 10px 18px; border-radius: 10px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; white-space: nowrap; z-index: 2; font-size: 0.85rem;">
                                <i class="fas fa-copy"></i> <?php echo $vt['copy']; ?>
                            </button>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="method_id" id="hidden-method-id">
                            
                            <div class="form-group mb-4">
                                <label style="display:block; margin-bottom: 0.5rem; font-weight: 800; opacity: 0.5; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px;"><?php echo $vt['amount']; ?></label>
                                <input type="number" step="0.01" name="amount" required style="width: 100%; background: #000; border: 1px solid rgba(255,255,255,0.08); padding: 1rem; border-radius: 12px; color:#fff; font-size: 1.2rem; outline:none; transition: 0.3s; font-weight: 700;" placeholder="0.00">
                            </div>

                            <div id="dynamic-fields"></div>

                            <button type="submit" class="btn btn-primary btn-block" style="width: 100%; padding: 1.4rem; border-radius: 15px; font-weight: 900; font-size: 1.1rem; margin-top: 1.5rem; box-shadow: 0 12px 25px rgba(211,47,47,0.25); border:none; background: var(--primary-red);">
                                <i class="fas fa-paper-plane" style="margin-right:8px;"></i> <?php echo $vt['submit']; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Info Modal -->
<div id="info-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(12px); z-index: 9999; align-items: center; justify-content: center;">
    <div class="animate-fade" style="background: #1a1a1a; width: 90%; max-width: 450px; padding: 3rem; border-radius: 35px; border: 1px solid rgba(255,255,255,0.05); text-align: center; box-shadow: 0 25px 50px rgba(0,0,0,0.5);">
        <i class="fas fa-info-circle" style="font-size: 3rem; color: var(--primary-red); margin-bottom: 1.5rem;"></i>
        <h3 style="margin-bottom: 1rem; font-weight: 900; font-size: 1.5rem;"><?php echo $vt['instructions']; ?></h3>
        <div id="info-modal-content" style="opacity: 0.8; line-height: 1.8; margin-bottom: 2.5rem; text-align: left; background: rgba(0,0,0,0.3); padding: 1.5rem; border-radius: 20px; font-size: 0.95rem;"></div>
        <button onclick="closeInfoModal()" class="btn btn-primary" style="width: 100%; padding: 1.2rem; border-radius: 15px; font-weight: 800; background: var(--primary-red); border:none; color: #fff;">Tamam</button>
    </div>
</div>

<style>
.method-item:hover { background: rgba(255,255,255,0.04) !important; transform: translateY(-3px); }
.method-item.active { border-color: rgba(211,47,47,0.3) !important; background: rgba(211,47,47,0.08) !important; transform: translateX(8px); box-shadow: 0 15px 30px rgba(0,0,0,0.3); }
.method-item.active .active-indicator { opacity: 1 !important; }

.info-text-field { background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border: 1px solid rgba(255,255,255,0.1); font-size: 0.85rem; opacity: 0.7; }
.redirect-btn-field { display: block; background: #fff; color: #000; text-align: center; padding: 1rem; border-radius: 12px; text-decoration: none; font-weight: 800; margin-bottom: 1.5rem; transition: 0.3s; font-size: 0.9rem; }
.redirect-btn-field:hover { background: var(--primary-red); color: #fff; }
input:focus { border-color: var(--primary-red) !important; background: rgba(211,47,47,0.02) !important; }
.btn-copy:active { transform: scale(0.95); opacity: 0.8; }

@media (max-width: 900px) {
    .deposit-layout { flex-direction: column; }
    .methods-side { flex: none; width: 100%; margin-bottom: 2rem; }
    .method-item.active { transform: translateY(-8px); }
    #form-container-placeholder { margin-top: 0; }
    .container { padding: 0 15px; }
}
</style>

<script>
let currentMethodInstructions = "";

function selectMethod(m) {
    // UI states
    document.getElementById('no-method-selected').style.display = 'none';
    document.getElementById('deposit-form-box').style.display = 'block';
    
    document.querySelectorAll('.method-item').forEach(el => el.classList.remove('active'));
    document.getElementById('method-' + m.id).classList.add('active');
    
    const lang = '<?php echo $lang; ?>';
    document.getElementById('selected-method-name').innerText = lang == 'tr' ? m.name_tr : (m.name_en || m.name_tr);
    currentMethodInstructions = lang == 'tr' ? m.instructions_tr : (m.instructions_en || m.instructions_tr);
    document.getElementById('hidden-method-id').value = m.id;

    const accDetails = lang == 'tr' ? m.account_details_tr : (m.account_details_en || m.account_details_tr);
    const accBox = document.getElementById('acc-details-box');
    if (accDetails) {
        accBox.style.display = 'flex';
        document.getElementById('acc-details-text').innerText = accDetails;
    } else {
        accBox.style.display = 'none';
    }

    let fields = [];
    try {
        fields = m.fields ? JSON.parse(m.fields) : [];
    } catch(e) { fields = []; }
    
    let html = '';
    fields.forEach(f => {
        const label = f.label || 'Alan';
        const isReq = (f.required === true || f.required === "true" || f.required === 1) ? 'required' : '';
        const meta = f.meta || '';
        
        if (f.type === 'info') {
            html += `<div class="info-text-field"><i class="fas fa-info-circle"></i> ${label} ${meta ? '<br><small style="opacity:0.6">'+meta+'</small>' : ''}</div>`;
        } else if (f.type === 'redirect') {
            html += `<a href="${meta}" target="_blank" class="redirect-btn-field"><i class="fas fa-external-link-alt"></i> ${label}</a>`;
        } else if (f.type === 'file') {
            html += `<div class="form-group mb-4">
                        <label style="display:block; margin-bottom: 0.5rem; font-weight: 800; opacity: 0.5; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px;">${label} ${isReq ? '*' : ''}</label>
                        <div style="position:relative;">
                            <input type="file" name="${label}" ${isReq} style="width: 100%; background: rgba(255,255,255,0.05); border: 2px dashed rgba(255,255,255,0.1); padding: 1.2rem; border-radius: 12px; color:#fff; font-size: 0.85rem; outline:none; transition: 0.3s; cursor:pointer; text-align:center;">
                        </div>
                     </div>`;
        } else {
            const inputType = f.type || 'text';
            html += `<div class="form-group mb-4">
                        <label style="display:block; margin-bottom: 0.5rem; font-weight: 800; opacity: 0.5; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px;">${label} ${isReq ? '*' : ''}</label>
                        <input type="${inputType}" name="${label}" ${isReq} placeholder="${meta}" style="width: 100%; background: #000; border: 1px solid rgba(255,255,255,0.08); padding: 1rem; border-radius: 12px; color:#fff; font-size: 1rem; outline:none; transition: 0.3s; font-family:inherit;">
                     </div>`;
        }
    });
    document.getElementById('dynamic-fields').innerHTML = html;
    
    if(window.innerWidth < 900) {
        window.scrollTo({ top: document.getElementById('deposit-form-box').offsetTop - 100, behavior: 'smooth' });
    }
}

function showInfoModal() {
    document.getElementById('info-modal-content').innerText = currentMethodInstructions || "Talimat bulunmuyor.";
    document.getElementById('info-modal').style.display = 'flex';
}
function closeInfoModal() { document.getElementById('info-modal').style.display = 'none'; }

function copyAccDetails() {
    const text = document.getElementById('acc-details-text').innerText;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.getElementById('btn-copy');
        const oldHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> <?php echo $vt['copied']; ?>';
        btn.style.background = '#4CAF50';
        btn.style.color = '#fff';
        setTimeout(() => {
            btn.innerHTML = oldHtml;
            btn.style.background = '#fff';
            btn.style.color = '#000';
        }, 2000);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
