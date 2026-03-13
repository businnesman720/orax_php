<?php
$admin_files = ['dashboard.php', 'videos.php', 'categories.php', 'users.php', 'settings.php'];

$replace_block = <<<'EOD'
<?php if(!empty($current_settings['logo']) && file_exists('../' . $current_settings['logo'])): ?>
        <div style="text-align: center; margin-bottom: 4rem;">
            <img src="../<?php echo $current_settings['logo']; ?>" alt="Logo" style="width: <?php echo !empty($current_settings['admin_logo_width']) ? htmlspecialchars($current_settings['admin_logo_width']) : '200px'; ?>; max-width: 100%; object-fit: contain; filter: drop-shadow(0 0 20px rgba(211, 47, 47, 0.3));">
        </div>
    <?php else: ?>
        <div class="sidebar-logo">ORAX</div>
    <?php endif; ?>
EOD;

foreach ($admin_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);

        // Fetch settings if not already fetched in the file (only dashboard might not have it)
        if (strpos($content, '$current_settings') === false && strpos($content, "SELECT * FROM settings") === false) {
            $settings_query = <<<'EOD'
// Get current settings
$stmt_settings = $pdo->query("SELECT * FROM settings");
$current_settings = [];
while ($row = $stmt_settings->fetch(PDO::FETCH_ASSOC)) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}
?>
EOD;
            $content = preg_replace('/(\$t = \$texts\[\$lang\];\s*\n*.*?)(\?>)/is', "$1\n$settings_query", $content, 1);
        }

        // Replace logo
        $content = preg_replace('/<div class="sidebar-logo">.*?<\/div>/', $replace_block, $content);
        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
?>

