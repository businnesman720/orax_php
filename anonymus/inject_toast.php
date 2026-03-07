<?php
$files = ['dashboard.php', 'videos.php', 'categories.php', 'users.php', 'settings.php'];

$css = <<<CSS
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
CSS;

$html = <<<HTML
    <?php if(isset(\$_GET['msg'])): ?>
        <div class="msg-toast <?php echo \$_GET['msg'] == 'error' ? 'error' : ''; ?>">
            <i class="fas <?php echo \$_GET['msg'] == 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
            <?php 
                if(\$_GET['msg'] == 'success') echo isset(\$t['saved']) ? \$t['saved'] : 'İşlem Başarılı!';
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
HTML;

foreach ($files as $file) {
    if(file_exists($file)) {
        $content = file_get_contents($file);

        // 1. Remove old msg-success rules if any
        $content = preg_replace('/\.msg-success\s*\{.*?\}/s', '', $content);
        // Remove old success messages HTML (like in settings.php)
        $content = preg_replace('/<\?php if \(isset\(\$_GET\[\'msg\'\]\).*?class="msg-success".*?<\?php endif; \?>/s', '', $content);
        
        // 2. Add the CSS if not exists
        if(strpos($content, '.msg-toast') === false) {
            $content = preg_replace('/(<\/style>)/i', $css . "\n$1", $content, 1);
        }

        // 3. Add the HTML right after <header class="header-bar">...</header>
        if(strpos($content, '<div class="msg-toast') === false) {
            $content = preg_replace('/(<\/header>)/i', "$1\n" . $html, $content, 1);
        }

        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
?>
