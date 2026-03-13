<?php
$files = ['dashboard.php', 'videos.php', 'categories.php', 'users.php', 'settings.php'];

$old_str = '<img src="../<?php echo $current_settings[\'logo\']; ?>" alt="Logo" style="max-height: 50px; filter: drop-shadow(0 0 20px rgba(211, 47, 47, 0.3));">';
$new_str = '<img src="../<?php echo $current_settings[\'logo\']; ?>" alt="Logo" style="width: <?php echo !empty($current_settings[\'admin_logo_width\']) ? htmlspecialchars($current_settings[\'admin_logo_width\']) : \'200px\'; ?>; max-width: 100%; object-fit: contain; filter: drop-shadow(0 0 20px rgba(211, 47, 47, 0.3));">';

foreach ($files as $file) {
    if(file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace($old_str, $new_str, $content);
        file_put_contents($file, $content);
        echo 'Updated ' . $file . "\n";
    }
}
?>

