<?php include 'includes/header.php'; ?>
<?php include 'includes/db.php'; ?>

<div class="container animate-fade">
    <div class="category-header-wrapper" style="margin-bottom: 3.5rem; position: relative; padding-bottom: 0.8rem; border-bottom: 2px solid rgba(255,255,255,0.05);">
        <h1 style="font-size: 2.8rem; font-weight: 800; letter-spacing: -1px; margin: 0; position: relative; z-index: 1;">
            <?php echo $t['categories']; ?>
        </h1>
        <div class="header-accent-line" style="position: absolute; bottom: -2px; left: 0; width: 80px; height: 3px; background: var(--primary-red); border-radius: 50px; box-shadow: 0 0 15px var(--primary-red); z-index: 2;"></div>
        <p style="opacity: 0.4; font-size: 0.9rem; margin-top: 0.5rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
            <?php 
                $total_cats = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
                echo $total_cats . ' ' . ($lang == 'tr' ? 'Kategori' : 'Categories'); 
            ?>
        </p>
    </div>

    <div class="video-grid">
        <?php
        $categories = $pdo->query("SELECT *, (SELECT COUNT(*) FROM videos WHERE category_id = categories.id) as v_count FROM categories ORDER BY v_count DESC")->fetchAll();
        foreach($categories as $cat):
            $cat_name = ($lang == 'tr') ? $cat['name_tr'] : $cat['name_en'];
        ?>
        <a href="category.php?slug=<?php echo $cat['slug']; ?>" class="category-card" style="overflow: hidden; border-radius: 20px;">
            <div class="category-image">
                <img src="https://picsum.photos/seed/cat_img_<?php echo $cat['id']; ?>/400/225" alt="<?php echo $cat_name; ?>" style="transition: 0.5s;">
                <div class="category-overlay" style="background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, transparent 100%); display: flex; align-items: flex-end; padding: 1.5rem;">
                    <div style="width: 100%;">
                        <h3 class="category-title" style="font-size: 1.4rem; font-weight: 800; margin-bottom: 2px; text-shadow: 0 2px 10px rgba(0,0,0,0.5);"><?php echo $cat_name; ?></h3>
                        <p style="font-size: 1.1rem; font-weight: 900; color: #dcdcdc; text-shadow: 0 2px 10px rgba(0,0,0,1); display: flex; align-items: center; gap: 6px; margin: 0;">
                            <i class="fas fa-play-circle" style="color: var(--primary-red); font-size: 0.8rem; opacity: 0.9;"></i> 
                            <?php echo $cat['v_count']; ?>
                        </p>
                    </div>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
