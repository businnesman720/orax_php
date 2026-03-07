<?php
$files = ['dashboard.php', 'videos.php', 'categories.php', 'users.php'];
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    
    // Add translation if not exists
    if (strpos($content, "'settings' => 'Site Settings'") === false) {
        // Find 'users' => 'Users' and append
        $content = preg_replace("/('users'\s*=>\s*'Users')/", "$1,\n        'settings' => 'Site Settings'", $content);
        // Find 'users' => 'Kullanıcılar' and append
        $content = preg_replace("/('users'\s*=>\s*'Kullanıcılar')/", "$1,\n        'settings' => 'Site Ayarları'", $content);
    }
    
    // Add nav item if not exists
    if (strpos($content, "settings.php") === false) {
        $content = preg_replace('/(<li><a href="users\.php".*?<\/a><\/li>)/s', "$1\n        <li><a href=\"settings.php\"><i class=\"fas fa-cog\"></i> <?php echo \$t['settings']; ?></a></li>", $content);
    }
    
    file_put_contents($file, $content);
    echo "Updated $file\n";
}
?>
