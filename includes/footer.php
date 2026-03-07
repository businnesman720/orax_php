        </main>
    </div> <!-- .app-container end -->

    <!-- Global Orax Dialog -->
<div class="orax-dialog-overlay" id="global-dialog-overlay">
    <div class="orax-dialog" id="global-dialog">
        <div class="dialog-header">
            <i class="fas fa-exclamation-circle"></i>
            <span id="dialog-title">Onay Gerekli</span>
        </div>
        <div class="dialog-body" id="dialog-message">
            Bu işlemi yapmak istediğinizden emin misiniz?
        </div>
        <div class="dialog-footer">
            <button class="btn-dialog btn-dialog-cancel" id="dialog-cancel">İptal</button>
            <button class="btn-dialog btn-dialog-confirm" id="dialog-confirm">Onayla</button>
        </div>
    </div>
</div>

<script>
    // Global Dialog System
    function showConfirmDialog(title, message, onConfirm) {
        const overlay = document.getElementById('global-dialog-overlay');
        const dialog = document.getElementById('global-dialog');
        const titleEl = document.getElementById('dialog-title');
        const messageEl = document.getElementById('dialog-message');
        const confirmBtn = document.getElementById('dialog-confirm');
        const cancelBtn = document.getElementById('dialog-cancel');

        titleEl.textContent = title;
        messageEl.textContent = message;
        overlay.style.display = 'flex';
        setTimeout(() => dialog.classList.add('active'), 10);

        const close = () => {
            dialog.classList.remove('active');
            setTimeout(() => overlay.style.display = 'none', 300);
        };

        confirmBtn.onclick = () => { close(); onConfirm(); };
        cancelBtn.onclick = close;
        overlay.onclick = (e) => { if(e.target === overlay) close(); };
    }
</script>

    <footer class="main-footer">
        <?php if(!empty($site_settings['logo']) && file_exists(__DIR__ . '/../' . $site_settings['logo'])): ?>
            <img src="<?php echo $site_settings['logo']; ?>" alt="Logo" style="max-height: 50px; margin-bottom: 2rem; filter: drop-shadow(0 0 10px rgba(211, 47, 47, 0.2));">
        <?php else: ?>
            <div class="logo"><?php echo !empty($site_settings['site_title']) ? explode(' ', $site_settings['site_title'])[0] : 'ORAX'; ?></div>
        <?php endif; ?>
        <p style="opacity: 0.5; font-size: 0.85rem; letter-spacing: 1px;"><?php echo $t['footer_text']; ?></p>
    </footer>

    <script src="assets/js/main.js"></script>

<?php
// Yaş Doğrulaması Uyarısı 
if (isset($site_settings['age_warning']) && $site_settings['age_warning'] == '1'):
?>
    <div class="orax-dialog-overlay" id="age-verify-overlay" style="z-index: 999999; backdrop-filter: blur(10px);">
        <div class="orax-dialog active" id="age-verify-dialog" style="text-align: center; padding: 3rem;">
            <div style="font-size: 4rem; color: var(--primary-red); margin-bottom: 1rem;"><i class="fas fa-exclamation-triangle"></i></div>
            <h2 style="font-size: 2rem; margin-bottom: 1rem;">18+ Yaş Sınırı</h2>
            <p style="opacity: 0.7; font-size: 1.1rem; line-height: 1.6; margin-bottom: 2rem;">
                Bu web sitesi, yalnızca 18 yaş ve üzerindeki yetişkinlere yönelik içerikler barındırmaktadır. 
                "18 YAŞINDAN BÜYÜĞÜM (Giriş)" butonuna tıklayarak, en az 18 yaşında olduğunuzu onaylamış olursunuz.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button onclick="window.location.href='https://google.com'" class="btn" style="background: rgba(255,255,255,0.1); padding: 1rem 2rem; border-radius: 12px; color: white;">18 yaşından küçüğüm (Çıkış)</button>
                <button onclick="acceptAge()" class="btn btn-primary" style="padding: 1rem 2rem; border-radius: 12px;">18 Yaşından Büyüğüm (Giriş)</button>
            </div>
        </div>
    </div>
    <script>
        if (!localStorage.getItem('age_verified')) {
            document.getElementById('age-verify-overlay').style.display = 'flex';
        } else {
            document.getElementById('age-verify-overlay').style.display = 'none';
        }
        function acceptAge() {
            localStorage.setItem('age_verified', 'yes');
            document.getElementById('age-verify-overlay').style.display = 'none';
        }
    </script>
<?php endif; ?>

<?php
// Google Analytics / Head Code (Bu kısım normalde head'e konur ancak body sonuna da eklenebilir. Veya doğrudan script etiketi basarız)
if (!empty($site_settings['google_analytics'])) {
    $ga = $site_settings['google_analytics'];
    if (strpos($ga, '<script') !== false) {
        // Zaten script etiketiyle gelmişse direkt bas
        echo $ga;
    } else {
        // Sadece ID gelmişse (G- veya UA- yapısı)
        echo "
        <script async src='https://www.googletagmanager.com/gtag/js?id={$ga}'></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', '{$ga}');
        </script>
        ";
    }
}
?>

</body>
</html>
