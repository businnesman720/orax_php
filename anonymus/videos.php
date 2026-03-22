<?php
include 'auth.php';
include '../includes/db.php';

// Dil ayarı (Rule 5: default English)
if (!isset($_SESSION['admin_lang'])) {
    $_SESSION['admin_lang'] = 'tr';
}
if (isset($_GET['lang'])) {
    $_SESSION['admin_lang'] = $_GET['lang'] == 'tr' ? 'tr' : 'en';
    $clean_url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $clean_url");
    exit;
}
$lang = $_SESSION['admin_lang'];

$texts = [
    'en' => [
        'title' => 'Video Management',
        'tab_title' => 'Advanced Video Management',
        'add_video' => 'Add New Video',
        'edit_video' => 'Edit Video',
        'save' => 'Save Video',
        'cancel' => 'Cancel',
        'table_title' => 'Title',
        'table_cat' => 'Category',
        'table_views' => 'Views',
        'table_actions' => 'Actions',
        'placeholder_tr' => 'Title (TR)',
        'placeholder_en' => 'Title (EN - Optional)',
        'desc_tr' => 'Description (TR)',
        'desc_en' => 'Description (EN - Optional)',
        'type' => 'Video Source',
        'url' => 'Video URL / Embed Code',
        'file' => 'Video File',
        'duration' => 'Duration',
        'thumbnail' => 'Thumbnail',
        'crop' => 'Crop Thumbnail',
        'auto_thumb' => 'Capture from Video',
        'success' => 'Operation successful!',
        'confirm_delete' => 'Delete this video?',
        'dashboard' => 'Dashboard',
        'videos' => 'Videos',
        'categories' => 'Categories',
        'users' => 'Users',
        'settings' => 'Site Settings',
        'logout' => 'Safe Logout',
        'view_site' => 'Live View',
        'reports' => 'Reports',
        'source_bunny' => 'Bunny.net Storage',
        'source_bunny_stream' => 'Bunny.net Stream (Recommended)',
        'error_large_file' => 'Error: File too large for server limits! Reset php.ini configuration (Recommended: 1024M)'
    ],
    'tr' => [
        'title' => 'Video Yönetimi',
        'tab_title' => 'Gelişmiş Video Yönetimi',
        'add_video' => 'Yeni Video Ekle',
        'edit_video' => 'Videoyu Düzenle',
        'save' => 'Videoyu Kaydet',
        'cancel' => 'İptal',
        'table_title' => 'Başlık',
        'table_cat' => 'Kategori',
        'table_views' => 'İzlenme',
        'table_actions' => 'İşlemler',
        'placeholder_tr' => 'Başlık (TR)',
        'placeholder_en' => 'Başlık (EN - Opsiyonel)',
        'desc_tr' => 'Açıklama (TR)',
        'desc_en' => 'Açıklama (EN - Opsiyonel)',
        'type' => 'Video Kaynağı',
        'url' => 'Video URL / Embed Kodu',
        'file' => 'Video Dosyası',
        'duration' => 'Süre',
        'thumbnail' => 'Küçük Resim',
        'crop' => 'Resmi Kırp',
        'auto_thumb' => 'Videodan Kare Al',
        'success' => 'İşlem başarılı!',
        'confirm_delete' => 'Videoyu silmek istediğinize emin misiniz?',
        'dashboard' => 'Panel',
        'videos' => 'Videolar',
        'categories' => 'Kategoriler',
        'users' => 'Kullanıcılar',
        'settings' => 'Site Ayarları',
        'logout' => 'Güvenli Çıkış',
        'view_site' => 'Siteyi Gör',
        'reports' => 'Raporlar',
        'source_bunny' => 'Bunny.net Storage',
        'source_bunny_stream' => 'Bunny.net Stream (Önerilen)',
        'error_large_file' => 'Hata: Dosya sunucu limitine takıldı! php.ini ayarlarını (Örn: 1024M) yükseltin.'
    ]
];
$t = $texts[$lang];

// Helper: Azerbaijan Slug
function generateSlug($text) {
    $map = [
        'ə' => 'e', 'Ə' => 'e', 'ş' => 's', 'Ş' => 's', 'ç' => 'c', 'Ç' => 'c',
        'ğ' => 'g', 'Ğ' => 'g', 'ö' => 'o', 'Ö' => 'o', 'ü' => 'u', 'Ü' => 'u',
        'ı' => 'i', 'İ' => 'i'
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\s-]/', '', strtolower($text));
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return $text;
}

// Get current settings
$stmt_settings = $pdo->query("SELECT * FROM settings");
$current_settings = [];
while ($row = $stmt_settings->fetch(PDO::FETCH_ASSOC)) {
    $current_settings[$row['setting_key']] = $row['setting_value'];
}

// Handle Form POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    // 40MB PHP limit check: If POST is empty but method is POST, file was too large
    if (empty($_POST) || empty($_FILES) && $_POST['action'] == 'add') {
        header("Location: videos.php?msg=error_large_file");
        exit;
    }

    $id = $_POST['id'] ?? null;
    $category_id = $_POST['category_id'];
    $title_tr = $_POST['title_tr'];
    $title_en = !empty($_POST['title_en']) ? $_POST['title_en'] : $title_tr;
    $desc_tr = $_POST['desc_tr'];
    $desc_en = !empty($_POST['desc_en']) ? $_POST['desc_en'] : $desc_tr;
    $video_type = $_POST['video_type'];
    $duration = $_POST['duration'];
    $quality = $_POST['quality'] ?? 'HD';
    $slug = generateSlug($title_en);

    // Video URL or File
    $video_url = $_POST['video_url'];
    if ($video_type == 'file') {
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
            $ext = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('vid_') . '.' . $ext;
            move_uploaded_file($_FILES['video_file']['tmp_name'], "../uploads/videos/" . $filename);
            $video_url = "uploads/videos/" . $filename;
        } elseif (!empty($_POST['existing_video_url'])) {
            $video_url = $_POST['existing_video_url'];
        }
    }

    if ($video_type == 'bunny') {
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
            $bunny_storage = $current_settings['bunny_storage_name'] ?? '';
            $bunny_api_key = $current_settings['bunny_api_key'] ?? '';
            $bunny_pull_url = $current_settings['bunny_pull_url'] ?? '';
            $bunny_region = !empty($current_settings['bunny_region']) ? $current_settings['bunny_region'] : 'de';

            if ($bunny_storage && $bunny_api_key && $bunny_pull_url) {
                $ext = pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('cdn_') . '.' . $ext;
                
                $base_url = "https://storage.bunnycdn.com";
                if ($bunny_region != 'de' && !empty($bunny_region)) {
                    $base_url = "https://{$bunny_region}.storage.bunnycdn.com";
                }
                
                $upload_url = "{$base_url}/{$bunny_storage}/{$filename}";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $upload_url);
                curl_setopt($ch, CURLOPT_PUT, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "AccessKey: {$bunny_api_key}",
                    "Content-Type: application/octet-stream",
                ]);
                
                $fh = fopen($_FILES['video_file']['tmp_name'], 'r');
                curl_setopt($ch, CURLOPT_INFILE, $fh);
                curl_setopt($ch, CURLOPT_INFILESIZE, filesize($_FILES['video_file']['tmp_name']));

                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                fclose($fh);

                if ($http_code == 201 || $http_code == 200) {
                    $video_url = rtrim($bunny_pull_url, '/') . '/' . $filename;
                    // Video tipini artık 'url' olarak kaydedebiliriz çünkü direkt link oldu
                    // Ama 'bunny' olarak kalsın ki ayırt edilebilsin
                } else {
                    header("Location: videos.php?msg=error_cdn");
                    exit;
                }
            }
        } elseif (!empty($_POST['existing_video_url'])) {
            $video_url = $_POST['existing_video_url'];
        }
    }

    if ($video_type == 'bunny_stream') {
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
            $library_id = $current_settings['bunny_stream_library_id'] ?? '';
            $api_key = $current_settings['bunny_stream_api_key'] ?? '';
            $pull_url = $current_settings['bunny_stream_pull_url'] ?? '';

            if ($library_id && $api_key) {
                // 1. Create video object in Bunny Stream
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://video.bunnycdn.com/library/{$library_id}/videos");
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "AccessKey: {$api_key}",
                    "Content-Type: application/json",
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["title" => $title_tr]));
                $response = curl_exec($ch);
                $resp_data = json_decode($response, true);
                curl_close($ch);

                if (isset($resp_data['guid'])) {
                    $guid = $resp_data['guid'];
                    
                    // 2. Upload the file
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://video.bunnycdn.com/library/{$library_id}/videos/{$guid}");
                    curl_setopt($ch, CURLOPT_PUT, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ["AccessKey: {$api_key}"]);
                    
                    $fh = fopen($_FILES['video_file']['tmp_name'], 'r');
                    curl_setopt($ch, CURLOPT_INFILE, $fh);
                    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($_FILES['video_file']['tmp_name']));
                    
                    $upload_resp = curl_exec($ch);
                    $upload_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    fclose($fh);

                    if ($upload_code == 200 || $upload_code == 201) {
                        // Ensure hostname is full URL
                        if (strpos($pull_url, '.b-cdn.net') === false && !empty($pull_url)) {
                            $pull_url .= '.b-cdn.net';
                        }
                        $video_url = "https://{$pull_url}/{$guid}/playlist.m3u8";
                        // Automatically set thumbnail if empty
                        if (empty($thumbnail)) {
                            $thumbnail = "https://{$pull_url}/{$guid}/thumbnail.jpg";
                        }
                    } else {
                        header("Location: videos.php?msg=error_bunny_upload");
                        exit;
                    }
                } else {
                    header("Location: videos.php?msg=error_bunny_create");
                    exit;
                }
            }
        } elseif (!empty($_POST['existing_video_url'])) {
            $video_url = $_POST['existing_video_url'];
        }
    }

    // Thumbnail
    $thumbnail = $_POST['thumbnail_url'] ?? '';
    
    // Log the incoming POST data length for debugging
    error_log("Thumbnail processing starts. Has cropped_image: " . (!empty($_POST['cropped_image']) ? 'YES (' . strlen($_POST['cropped_image']) . ' bytes)' : 'NO'));

    if (!empty($_POST['cropped_image'])) {
        $img = $_POST['cropped_image'];
        // Remove either data:image/png;base64, or data:image/jpeg;base64,
        $img = preg_replace('/^data:image\/\w+;base64,/', '', $img);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        
        if ($data !== false) {
            $filename = uniqid('thumb_') . '.png';
            $save_path = "../uploads/thumbnails/" . $filename;
            $result = file_put_contents($save_path, $data);
            if ($result !== false) {
                $thumbnail = "uploads/thumbnails/" . $filename;
                error_log("Thumbnail saved successfully. Path: " . $thumbnail . " Size: " . $result . " bytes");
            } else {
                error_log("FAILED to save thumbnail to " . $save_path);
            }
        } else {
            error_log("Base64 decode failed for cropped image data.");
        }
    } elseif (empty($thumbnail) && !empty($_POST['existing_thumbnail'])) {
        $thumbnail = $_POST['existing_thumbnail'];
        error_log("Using existing thumbnail: " . $thumbnail);
    }

    // Premium Flag
    $is_premium = isset($_POST['is_premium']) ? 1 : 0;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE videos SET category_id=?, title_tr=?, title_en=?, description_tr=?, description_en=?, video_url=?, video_type=?, duration=?, quality=?, thumbnail=?, slug=?, is_premium=? WHERE id=?");
        $stmt->execute([$category_id, $title_tr, $title_en, $desc_tr, $desc_en, $video_url, $video_type, $duration, $quality, $thumbnail, $slug, $is_premium, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO videos (category_id, title_tr, title_en, description_tr, description_en, video_url, video_type, duration, quality, thumbnail, slug, is_premium) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $title_tr, $title_en, $desc_tr, $desc_en, $video_url, $video_type, $duration, $quality, $thumbnail, $slug, $is_premium]);
    }
    header("Location: videos.php?msg=success");
    exit;
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: videos.php");
    exit;
}

$videos = $pdo->query("SELECT v.*, c.name_en as cat_name FROM videos v LEFT JOIN categories c ON v.category_id = c.id ORDER BY v.id DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORAX - <?php echo $t['title']; ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <style>
        :root {
            --admin-sidebar: #151515;
            --admin-content: #1e1e1e;
        }

        body {
            background-color: var(--admin-content);
            margin: 0; padding: 0;
            display: flex; height: 100vh;
            overflow: hidden;
            font-family: 'Outfit', sans-serif;
            color: white;
        }

        /* Sidebar styling - MATCHING DASHBOARD */
        .sidebar {
            width: 300px;
            background: var(--admin-sidebar);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            display: flex; flex-direction: column;
            padding: 2.5rem;
            transition: 0.4s;
            z-index: 100;
        }

        .sidebar-logo {
            font-size: 2.5rem; font-weight: 950; color: var(--primary-red);
            letter-spacing: 5px; margin-bottom: 4rem; text-shadow: 0 0 20px rgba(211, 47, 47, 0.3);
            text-align: center;
        }

        .side-nav { list-style: none; padding: 0; }
        .side-nav li { margin-bottom: 1rem; }
        .side-nav a {
            display: flex; align-items: center; gap: 1.2rem; padding: 1.2rem 1.5rem;
            text-decoration: none; color: white; opacity: 0.6; border-radius: 15px;
            font-weight: 600; transition: 0.4s; border: 1px solid transparent;
        }
        .side-nav a:hover, .side-nav a.active {
            opacity: 1; background: rgba(211, 47, 47, 0.08);
            color: var(--primary-red); border-color: rgba(211, 47, 47, 0.2);
            transform: translateX(5px);
        }

        .lang-pills-admin { display: flex; gap: 0.5rem; background: rgba(0,0,0,0.3); padding: 0.3rem; border-radius: 50px; }
        .lang-pills-admin a { text-decoration: none; color: white; padding: 0.4rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 700; opacity: 0.4; transition: 0.3s; }
        .lang-pills-admin a.active { background: var(--primary-red); opacity: 1; }

        /* Main Area */
        .main-pane {
            flex: 1; padding: 3rem; overflow-y: auto;
            background: radial-gradient(circle at top right, rgba(211, 47, 47, 0.03) 0%, rgba(30, 30, 30, 1) 50%);
        }

        .header-bar {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 4rem;
        }

        .header-bar h1 { font-size: 2.5rem; font-weight: 800; }

        .btn-add { 
            background: linear-gradient(135deg, var(--primary-red), var(--accent-red)); 
            color: white; padding: 1rem 2rem; border-radius: 15px; border: none; font-weight: 800; 
            cursor: pointer; display: flex; align-items: center; gap: 10px; transition: 0.4s; 
            box-shadow: 0 10px 20px rgba(211, 47, 47, 0.3);
        }
        .btn-add:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(211, 47, 47, 0.5); }

        /* Video Table */
        .video-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; }
        .video-table th { padding: 1.2rem; text-align: left; opacity: 0.4; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1.5px; }
        .video-row { background: rgba(255,255,255,0.02); transition: 0.3s; }
        .video-row:hover { background: rgba(255,255,255,0.05); }
        .video-row td { padding: 1rem 1.2rem; border-top: 1px solid rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.03); }
        .video-row td:first-child { border-radius: 20px 0 0 20px; border-left: 1px solid rgba(255,255,255,0.03); }
        .video-row td:last-child { border-radius: 0 20px 20px 0; border-right: 1px solid rgba(255,255,255,0.03); }

        .thumb-mini { width: 100px; height: 56px; object-fit: cover; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .badge-type { background: rgba(211, 47, 47, 0.1); color: var(--primary-red); padding: 4px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 800; border: 1px solid rgba(211, 47, 47, 0.2); }

        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; backdrop-filter: blur(20px); align-items: center; justify-content: center; padding: 2rem; }
        .modal-content { background: #1a1a1a; padding: 3rem; border-radius: 35px; width: 100%; max-width: 1000px; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 30px 60px rgba(0,0,0,0.5); overflow-y: auto; max-height: 90vh; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem; }
        .input-group { margin-bottom: 1.8rem; }
        .input-group label { display: block; margin-bottom: 0.8rem; font-size: 0.85rem; font-weight: 700; color: rgba(255,255,255,0.4); text-transform: uppercase; }
        .input-group input, .input-group textarea, .input-group select { width: 100%; padding: 1.1rem; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1); border-radius: 18px; color: #fff; outline: none; transition: 0.3s; }
        .input-group input:focus { border-color: var(--primary-red); }

        .preview-area { background: #000; border-radius: 20px; overflow: hidden; position: relative; aspect-ratio: 16/9; margin-top: 1rem; border: 1px dashed rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; }
        .preview-placeholder { position: absolute; font-size: 3rem; opacity: 0.1; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        #thumb-preview, #crop-img { max-width: 100%; max-height: 100%; object-fit: contain; z-index: 10; }

        .btn-circle { width: 45px; height: 45px; border-radius: 15px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05); color: #fff; cursor: pointer; border: none; transition: 0.3s; }
        .btn-circle:hover { background: var(--primary-red); transform: scale(1.1); }
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
</style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<main class="main-pane">
    <header class="header-bar">
        <div>
            <h1><?php echo $t['tab_title']; ?></h1>
            <p style="opacity: 0.5; margin-top: 0.5rem;"><?php echo count($videos); ?> videos managed</p>
        </div>
        <div style="display: flex; gap: 1rem;">
            <a href="../index.php" target="_blank" class="btn btn-primary" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); box-shadow: none;">
                <i class="fas fa-external-link-alt"></i> <?php echo $t['view_site']; ?>
            </a>
            <button class="btn-add" onclick="openModal()">
                <i class="fas fa-plus"></i> <?php echo $t['add_video']; ?>
            </button>
        </div>
    </header>
    <?php if(isset($_GET['msg'])): ?>
        <div class="msg-toast <?php echo $_GET['msg'] == 'success' ? '' : 'error'; ?>">
            <i class="fas <?php echo $_GET['msg'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php 
                echo $t[$_GET['msg']] ?? ($_GET['msg'] == 'success' ? 'İşlem Başarılı!' : 'Bir hata oluştu!');
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

    <table class="video-table">
        <thead>
            <tr>
                <th><?php echo $t['table_title']; ?></th>
                <th><?php echo $t['table_cat']; ?></th>
                <th>Source</th>
                <th><?php echo $t['duration']; ?></th>
                <th><?php echo $t['table_actions']; ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($videos as $v): ?>
            <tr class="video-row">
                <td style="display: flex; align-items: center; gap: 15px;">
                    <?php 
                        $thumb_path = $v['thumbnail'];
                        if (empty($thumb_path)) {
                            $thumb_src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYwIiBoZWlnaHQ9IjkwIiB2aWV3Qm94PSIwIDAgMTYwIDkwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxNjAiIGhlaWdodD0iOTAiIGZpbGw9IiMxYTFhMWEiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZmlsbD0iIzhhOGE4YSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5PcmF4PC90ZXh0Pjwvc3ZnPg==';
                        } else {
                            $thumb_src = (strpos($thumb_path, 'http') === 0) ? $thumb_path : '../' . ltrim($thumb_path, '/');
                        }
                    ?>
                    <img src="<?php echo $thumb_src; ?>" class="thumb-mini" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQwIiBoZWlnaHQ9IjM2MCIgdmlld0JveD0iMCAwIDY0MCAzNjAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjY0MCIgaGVpZ2h0PSIzNjAiIGZpbGw9IiMxYTFhMWEiLz48L3N2Zz4=';">
                    <div>
                        <div style="font-weight: 700;">
                            <?php echo htmlspecialchars($v['title_tr']); ?>
                            <?php if(isset($v['is_premium']) && $v['is_premium']): ?>
                                <span style="background: gold; color: black; font-size: 0.6rem; padding: 2px 6px; border-radius: 4px; vertical-align: middle; margin-left: 5px; font-weight: 950;"><i class="fas fa-gem"></i> PREMIUM</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.75rem; opacity: 0.3;"><?php echo $v['slug']; ?></div>
                    </div>
                </td>
                <td><span style="opacity: 0.6; font-size: 0.85rem; font-weight: 600;"><?php echo $v['cat_name']; ?></span></td>
                <td><span class="badge-type"><?php echo strtoupper($v['video_type']); ?></span></td>
                <td><i class="far fa-clock" style="opacity: 0.3;"></i> <?php echo $v['duration']; ?></td>
                <td>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn-circle" onclick='editVideo(<?php echo htmlspecialchars(json_encode($v), ENT_QUOTES, "UTF-8"); ?>)'><i class="fas fa-pen"></i></button>
                        <a href="?delete=<?php echo $v['id']; ?>" class="btn-circle" onclick="return confirm('<?php echo $t['confirm_delete']; ?>')"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

<div class="modal" id="videoModal">
    <div class="modal-content">
        <h2 id="modalTitle" style="margin-bottom: 2.5rem; font-size: 2rem; font-weight: 800;"><?php echo $t['add_video']; ?></h2>
        <form method="POST" enctype="multipart/form-data" id="video-form" onsubmit="return handleFormSubmit()">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="vid_id">
            <input type="hidden" name="cropped_image" id="cropped_image_input">
            <input type="hidden" name="existing_video_url" id="existing_video_url">
            <input type="hidden" name="existing_thumbnail" id="existing_thumbnail">
            
            <div class="form-grid">
                <div>
                    <div class="input-group">
                        <label><?php echo $t['placeholder_tr']; ?></label>
                        <input type="text" name="title_tr" id="v_title_tr" required>
                    </div>
                    <div class="input-group">
                        <label><?php echo $t['placeholder_en']; ?></label>
                        <input type="text" name="title_en" id="v_title_en">
                    </div>
                    <div class="input-group">
                        <label><?php echo $t['table_cat']; ?></label>
                        <select name="category_id" id="v_cat" required>
                            <?php foreach($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo $c['name_tr']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-grid" style="gap: 1.5rem;">
                        <div class="input-group">
                            <label><?php echo $t['type']; ?></label>
                            <select name="video_type" id="v_type" onchange="toggleVideoInputs(this.value)">
                                <option value="url">Link / URL</option>
                                <option value="embed">Embed Code</option>
                                <option value="file">Local File</option>
                                <option value="bunny_stream"><?php echo $t['source_bunny_stream']; ?></option>
                                <option value="bunny"><?php echo $t['source_bunny']; ?></option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label><?php echo $t['duration']; ?></label>
                            <input type="text" name="duration" id="v_duration" placeholder="0:00">
                        </div>
                        <div class="input-group">
                            <label>Kalite</label>
                            <select name="quality" id="v_quality">
                                <option value="1080p" selected>1080p</option>
                                <option value="4K">4K</option>
                            </select>
                        </div>
                    </div>
                    <div class="input-group" id="url-input-div">
                        <label><?php echo $t['url']; ?></label>
                        <textarea name="video_url" id="v_url" rows="3" onchange="fetchMediaInfo(this.value)"></textarea>
                    </div>
                    <div class="input-group" id="file-input-div" style="display:none;">
                        <label><?php echo $t['file']; ?></label>
                        <input type="file" name="video_file" id="v_file" accept="video/*" onchange="handleLocalVideo(this)">
                        <div id="current-file-display" style="font-size: 0.75rem; color: var(--primary-red); margin-top: 5px; opacity: 0.8;"></div>
                    </div>
                    <div class="input-group" style="background: rgba(255,215,0,0.05); padding: 1.5rem; border-radius: 18px; border: 1px dashed rgba(255,215,0,0.3);">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: gold; font-weight: 900; margin-bottom: 0;">
                            <input type="checkbox" name="is_premium" id="v_is_premium" style="width: auto;">
                            <i class="fas fa-crown"></i> <?php echo ($lang == 'tr' ? 'PREMIUM VIDEO (SADECE VIP)' : 'PREMIUM VIDEO (VIP ONLY)'); ?>
                        </label>
                    </div>
                </div>

                <div>
                    <div class="input-group">
                        <label><?php echo $t['thumbnail']; ?></label>
                        <div style="display:flex; gap: 0.8rem; margin-bottom: 0.8rem;">
                            <input type="text" id="v_thumb_url" name="thumbnail_url" placeholder="Paste URL or CTRL+V" onchange="loadThumb(this.value)">
                            <label class="btn-circle" style="flex-shrink: 0; background: var(--primary-red); display: flex; align-items: center; justify-content: center;"><i class="fas fa-upload"></i><input type="file" id="v_thumb_file" hidden accept="image/*" onchange="loadThumbFile(this)"></label>
                        </div>
                    </div>
                    <div class="preview-area" id="thumb-preview-box">
                        <div id="placeholder-box" class="preview-placeholder"><i class="fas fa-image"></i></div>
                        <img id="thumb-preview" style="width: 100%; height: 100%; object-fit: cover; display: none;" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYwIiBoZWlnaHQ9IjkwIiB2aWV3Qm94PSIwIDAgMTYwIDkwIiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxNjAiIGhlaWdodD0iOTAiIGZpbGw9IiMxYTFhMWEiLz48L3N2Zz4=';">
                        <div class="crop-container" id="crop-box" style="display: none;"><img id="crop-img" style="max-width: 100%;"></div>
                        <button type="button" class="btn-add" id="crop-btn" style="position:absolute; bottom:15px; right:15px; display:none; padding: 0.6rem 1.2rem; font-size: 0.8rem;" onclick="confirmCrop()"><i class="fas fa-crop"></i> <?php echo $t['crop']; ?></button>
                    </div>
                    <button type="button" class="btn-add" onclick="captureFrame()" style="margin-top: 1.5rem; width:100%; background:rgba(255,255,255,0.05); color:#888; box-shadow:none; border: 1px solid rgba(255,255,255,0.1);"><i class="fas fa-camera"></i> <?php echo $t['auto_thumb']; ?></button>
                    
                    <div class="input-group" style="margin-top: 1.5rem;">
                        <label><?php echo $t['desc_tr']; ?></label>
                        <textarea name="desc_tr" id="v_desc_tr" rows="3"></textarea>
                    </div>
                    <div class="input-group" style="margin-top: 1.5rem;">
                        <label><?php echo $t['desc_en']; ?></label>
                        <textarea name="desc_en" id="v_desc_en" rows="3"></textarea>
                    </div>
                </div>
            </div>

            <div style="display:flex; gap: 1.5rem; margin-top: 3rem; justify-content: flex-end;">
                <button type="button" class="btn-add" style="background: rgba(255,255,255,0.05); color: #888; box-shadow: none;" onclick="closeModal()"><?php echo $t['cancel']; ?></button>
                <button type="submit" class="btn-add" style="padding: 1rem 3.5rem;"><?php echo $t['save']; ?></button>
            </div>
        </form>
    </div>
</div>

<video id="temp-video" style="display:none;" crossorigin="anonymous" playsinline muted></video>
<canvas id="auto-thumb-canvas" style="display:none;"></canvas>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
let cropper;
const modal = document.getElementById('videoModal');
const placeholder = document.getElementById('placeholder-box');
const thumbPreview = document.getElementById('thumb-preview');

function openModal() {
    document.getElementById('modalTitle').innerText = "<?php echo $t['add_video']; ?>";
    document.getElementById('vid_id').value = "";
    document.getElementById('video-form').reset();
    thumbPreview.src = "";
    thumbPreview.style.display = 'none';
    placeholder.style.display = 'flex';
    document.getElementById('crop-btn').style.display = 'none';
    document.getElementById('crop-box').style.display = 'none';
    if(cropper) cropper.destroy();
    modal.style.display = 'flex';
}

function closeModal() {
    modal.style.display = 'none';
    if(cropper) cropper.destroy();
}

function toggleVideoInputs(type) {
    document.getElementById('url-input-div').style.display = (type == 'file' || type == 'bunny' || type == 'bunny_stream') ? 'none' : 'block';
    document.getElementById('file-input-div').style.display = (type == 'file' || type == 'bunny' || type == 'bunny_stream') ? 'block' : 'none';
}

function loadThumb(url) {
    if(!url) return;
    placeholder.style.display = 'none';
    const img = document.getElementById('crop-img');
    img.src = url;
    thumbPreview.style.display = 'none';
    document.getElementById('crop-box').style.display = 'block';
    document.getElementById('crop-btn').style.display = 'block';
    
    if(cropper) cropper.destroy();
    setTimeout(() => {
        cropper = new Cropper(img, {
            aspectRatio: 16 / 9,
            viewMode: 2,
            autoCropArea: 1
        });
    }, 100);
}

function loadThumbFile(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => loadThumb(e.target.result);
        reader.readAsDataURL(input.files[0]);
    }
}

window.onpaste = function(event){
    const items = (event.clipboardData || event.originalEvent.clipboardData).items;
    for (let index in items) {
        const item = items[index];
        if (item.kind === 'file') {
            const blob = item.getAsFile();
            const reader = new FileReader();
            reader.onload = (e) => loadThumb(e.target.result);
            reader.readAsDataURL(blob);
        }
    }
};

function confirmCrop() {
    if(!cropper) return;
    const canvas = cropper.getCroppedCanvas({ width: 1280, height: 720 });
    const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
    thumbPreview.src = dataUrl;
    document.getElementById('cropped_image_input').value = dataUrl;
    thumbPreview.style.display = 'block';
    placeholder.style.display = 'none';
    document.getElementById('crop-box').style.display = 'none';
    document.getElementById('crop-btn').style.display = 'none';
    console.log("Cropped image saved to input.");
}

function handleFormSubmit() {
    if (document.getElementById('crop-box').style.display !== 'none' && cropper) {
        console.log("Auto-cropping before submit...");
        confirmCrop(); 
    }
    return true;
}

async function fetchMediaInfo(val) {
    console.log("Fetching media info for:", val);
    const ytMatch = val.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
    if (ytMatch) {
        const id = ytMatch[1];
        console.log("YouTube ID detected:", id);
        const embedUrl = `https://www.youtube.com/embed/${id}`;
        document.getElementById('v_url').value = embedUrl;
        document.getElementById('v_type').value = 'embed';
        
        const thumbUrl = `https://img.youtube.com/vi/${id}/maxresdefault.jpg`;
        console.log("Setting thumbnail URL:", thumbUrl);
        document.getElementById('v_thumb_url').value = thumbUrl;
        loadThumb(thumbUrl);

        // Arka plandan (PHP) detayları çek (süre ve açıklama için)
        try {
            console.log("Fetching deep metadata from backend...");
            const response = await fetch(`yt_api.php?id=${id}`);
            if (response.ok) {
                const data = await response.json();
                console.log("Backend data received:", data);
                
                // Başlık çekilmemişse (oEmbed alternatif olarak kullanılabilir ama backend daha sağlam)
                // Şimdilik backend'den gelen başlık boş gelebilir (regex'e bağlı), o yüzden oEmbed'i de tutuyoruz
                if (data.duration) {
                    document.getElementById('v_duration').value = data.duration;
                    console.log("Duration auto-filled:", data.duration);
                }
                if (data.description) {
                    document.getElementById('v_desc_tr').value = data.description;
                    document.getElementById('v_desc_en').value = data.description;
                    console.log("Description auto-filled.");
                }
            }
        } catch (e) {
            console.error("Backend fetch error:", e);
        }

        // YouTube oEmbed ile başlığı çek (her ihtimale karşı)
        try {
            console.log("Attempting oEmbed fetch for title...");
            const response = await fetch(`https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=${id}&format=json`);
            if (response.ok) {
                const data = await response.json();
                if (data.title) {
                    document.getElementById('v_title_tr').value = data.title;
                    document.getElementById('v_title_en').value = data.title;
                    console.log("Title auto-filled from oEmbed:", data.title);
                }
            }
        } catch (e) {
            console.error("oEmbed fetch error:", e);
        }
    } else {
        console.log("No YouTube ID match found.");
    }
}

function handleLocalVideo(input) {
    if (input.files && input.files[0]) {
        const url = URL.createObjectURL(input.files[0]);
        const video = document.getElementById('temp-video');
        video.src = url;
        video.onloadedmetadata = () => {
            let mins = Math.floor(video.duration / 60);
            let secs = Math.floor(video.duration % 60);
            document.getElementById('v_duration').value = `${mins}:${secs.toString().padStart(2, '0')}`;
        };
    }
}

function captureFrame() {
    const video = document.getElementById('temp-video');
    if (!video.src) return alert("Please select a video file first");
    
    // Ensure video is ready
    if (video.readyState < 2) {
        alert("Video is still loading, please wait...");
        return;
    }

    const canvas = document.getElementById('auto-thumb-canvas');
    const ctx = canvas.getContext('2d');
    
    // Grab frame from 2nd second (or duration/2 if shorter)
    video.currentTime = Math.min(2, video.duration / 2);
    
    video.onseeked = () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
        thumbPreview.src = dataUrl;
        thumbPreview.style.display = 'block';
        placeholder.style.display = 'none';
        
        // Also put it in the hidden input for form submission
        document.getElementById('cropped_image_input').value = dataUrl;
        console.log("Frame captured successfully");
    };
}

function editVideo(v) {
    openModal();
    document.getElementById('modalTitle').innerText = "<?php echo $t['edit_video']; ?>";
    document.getElementById('vid_id').value = v.id;
    document.getElementById('v_title_tr').value = v.title_tr;
    document.getElementById('v_title_en').value = v.title_en;
    document.getElementById('v_cat').value = v.category_id;
    document.getElementById('v_type').value = v.video_type;
    toggleVideoInputs(v.video_type);
    document.getElementById('v_url').value = v.video_url || "";
    document.getElementById('v_quality').value = v.quality || "1080p";
    document.getElementById('existing_video_url').value = v.video_url || "";
    document.getElementById('existing_thumbnail').value = v.thumbnail || "";
    document.getElementById('v_duration').value = v.duration || "0:00";
    document.getElementById('v_desc_tr').value = v.description_tr || "";
    if(document.getElementById('v_desc_en')) document.getElementById('v_desc_en').value = v.description_en || "";
    document.getElementById('v_is_premium').checked = (v.is_premium == 1);
    
    // Load existing video into temp-video for frame capture if it's a file
    const fileDisplay = document.getElementById('current-file-display');
    if(fileDisplay) fileDisplay.innerText = "";
    if(v.video_type === 'file' && v.video_url) {
        document.getElementById('temp-video').src = '../' + ltrim(v.video_url, '/');
        if(fileDisplay) fileDisplay.innerText = "Current File: " + v.video_url;
    }

    if(v.thumbnail) {
        let tSrc = v.thumbnail.startsWith('http') ? v.thumbnail : '../' + ltrim(v.thumbnail, '/');
        thumbPreview.src = tSrc;
        thumbPreview.style.display = 'block';
        placeholder.style.display = 'none';
        document.getElementById('v_thumb_url').value = (v.thumbnail.startsWith('http')) ? v.thumbnail : "";
    } else {
        thumbPreview.src = "";
        thumbPreview.style.display = 'none';
        placeholder.style.display = 'flex';
    }
}

function ltrim(str, chars) {
    if (!str) return "";
    return str.startsWith(chars) ? str.substring(1) : str;
}
</script>

</body>
</html>

