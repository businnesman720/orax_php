<?php
session_start();

$error = '';

// Dil ayarı (Rule 5: default English)
if (!isset($_SESSION['admin_lang'])) {
    $_SESSION['admin_lang'] = 'tr';
}
if (isset($_GET['lang'])) {
    $_SESSION['admin_lang'] = $_GET['lang'] == 'tr' ? 'tr' : 'en';
    // Mevcut sayfanın tam yolunu alalım (Query string temizlenmiş hali)
    $clean_url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: $clean_url");
    exit;
}
$lang = $_SESSION['admin_lang'];

$texts = [
    'en' => [
        'login_title' => 'Admin Access',
        'email_placeholder' => 'Admin Username',
        'password_placeholder' => 'Password',
        'login_btn' => 'Login Now',
        'error_msg' => 'Invalid credentials!'
    ],
    'tr' => [
        'login_title' => 'Yönetici Girişi',
        'email_placeholder' => 'Yönetici Adı',
        'password_placeholder' => 'Şifre',
        'login_btn' => 'Giriş Yap',
        'error_msg' => 'Hatalı giriş bilgileri!'
    ]
];
$t = $texts[$lang];

require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. Veritabanından kontrol et
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_user'] = $admin['username'];
        
        $base_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        header("Location: $base_path/dashboard.php");
        exit;
    } 
    // 2. Rule 8: admin/admin her zaman çalışmalı
    else if ($email === 'admin' && $password === 'admin') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = 'admin';
        
        $base_path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        header("Location: $base_path/dashboard.php");
        exit;
    } else {
        $error = $t['error_msg'];
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORAX - Admin Login</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #111 !important;
            margin: 0; padding: 0;
            display: flex; justify-content: center; align-items: center;
            height: 100vh;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
        }

        .login-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 70% 30%, rgba(211, 47, 47, 0.15) 0%, rgba(17, 17, 17, 1) 100%);
            z-index: -1;
        }

        .login-card {
            background: rgba(43, 43, 43, 0.6);
            backdrop-filter: blur(25px);
            padding: 4rem;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            width: 100%;
            max-width: 450px;
            box-shadow: 0 40px 100px rgba(0,0,0,0.8);
            transform: translateY(0);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .logo-text {
            font-size: 3rem;
            font-weight: 950;
            color: var(--primary-red);
            text-align: center;
            letter-spacing: 8px;
            margin-bottom: 0.5rem;
            text-shadow: 0 0 30px rgba(211, 47, 47, 0.5);
        }

        .login-subtitle {
            text-align: center;
            color: rgba(255,255,255,0.4);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 3rem;
        }

        .input-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            transition: 0.4s;
        }

        .input-box:focus-within {
            border-color: var(--primary-red);
            background: rgba(255, 255, 255, 0.06);
            box-shadow: 0 0 20px rgba(211, 47, 47, 0.2);
        }

        .input-box i { color: var(--primary-red); font-size: 1.2rem; margin-right: 1rem; }
        .input-box input {
            background: none; border: none; color: white; padding: 1.2rem 0; flex: 1; outline: none; font-size: 1rem;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-red), var(--accent-red));
            color: white; border: none; padding: 1.2rem; border-radius: 15px;
            width: 100%; font-weight: 800; font-size: 1.1rem; cursor: pointer; transition: 0.4s;
            box-shadow: 0 10px 30px rgba(211, 47, 47, 0.4);
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: scale(1.02);
            box-shadow: 0 15px 40px rgba(211, 47, 47, 0.6);
        }

        .error-msg {
            background: rgba(211, 47, 47, 0.1);
            color: #ff5252;
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border: 1px solid rgba(211, 47, 47, 0.2);
        }

        .lang-switch {
            position: absolute; bottom: 30px; display: flex; gap: 1rem;
        }
        .lang-switch a {
            color: white; opacity: 0.4; text-decoration: none; font-weight: 700; font-size: 0.8rem; transition: 0.3s;
        }
        .lang-switch a.active { opacity: 1; color: var(--primary-red); }

        /* Autofill Fix */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px #2b2b2b inset !important;
            -webkit-text-fill-color: white !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        .password-toggle {
            cursor: pointer;
            opacity: 0.5;
            transition: 0.3s;
            padding: 0.5rem;
            color: var(--primary-red);
        }
        .password-toggle:hover { opacity: 1; }
    </style>
</head>
<body>

<div class="login-bg"></div>

<div class="login-card">
    <div class="logo-text">ORAX</div>
    <div class="login-subtitle"><?php echo $t['login_title']; ?></div>

    <?php if($error): ?>
        <div class="error-msg"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-box">
            <i class="fas fa-envelope"></i>
            <input type="text" name="email" placeholder="<?php echo $t['email_placeholder']; ?>" required>
        </div>
        <div class="input-box">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="admin-pass" placeholder="<?php echo $t['password_placeholder']; ?>" required>
            <i class="fas fa-eye password-toggle" id="toggle-admin-pass"></i>
        </div>
        <button type="submit" class="btn-login"><?php echo $t['login_btn']; ?></button>
    </form>

    <div class="lang-switch">
        <a href="?lang=tr" class="<?php echo $lang == 'tr' ? 'active' : ''; ?>">TURKISH</a>
        <a href="?lang=en" class="<?php echo $lang == 'en' ? 'active' : ''; ?>">ENGLISH</a>
    </div>
</div>

<script>
    const togglePass = document.getElementById('toggle-admin-pass');
    const adminPass = document.getElementById('admin-pass');

    togglePass.addEventListener('click', function() {
        if (adminPass.type === 'password') {
            adminPass.type = 'text';
            this.classList.remove('fa-eye');
            this.classList.add('fa-eye-slash');
        } else {
            adminPass.type = 'password';
            this.classList.remove('fa-eye-slash');
            this.classList.add('fa-eye');
        }
    });
</script>
</body>
</html>

