<?php include 'includes/header.php'; ?>
<?php include 'includes/db.php'; 

$slug = isset($_GET['slug']) ? $_GET['slug'] : 'populer';
// Normalde bu DB'den çekilir, şimdilik mock kategori adı:
$cat_name = ucfirst($slug);
?>

<div class="container animate-fade">
    <header style="margin-bottom: 3rem; border-bottom: 2px solid var(--primary-red); padding-bottom: 1rem; display: inline-block;">
        <h1 style="font-size: 2.5rem;"># <?php echo $cat_name; ?></h1>
    </header>

    <div class="video-grid">
        <?php
        $categories = $pdo->query("SELECT * FROM categories")->fetchAll();
        foreach($categories as $cat):
            $cat_name = ($lang == 'tr') ? $cat['name_tr'] : $cat['name_en'];
        ?>
        <a href="category.php?slug=<?php echo $cat['slug']; ?>" class="category-card">
            <div class="category-image">
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIyNSIgdmlld0JveD0iMCAwIDQwMCAyMjUiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjQwMCIgaGVpZ2h0PSIyMjUiIGZpbGw9IiMxYTFhMWEiLz48L3N2Zz4=" alt="<?php echo $cat_name; ?>">
                <div class="category-overlay">
                    <h3 class="category-title"><?php echo $cat_name; ?></h3>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
