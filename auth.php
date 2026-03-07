<?php
include 'includes/db.php';
session_start();

$error = '';
$success = '';

// Dil ayarı (Header dahil edilmediği için burada da lazım olabilir)
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'tr';
}
$lang = $_SESSION['lang'];

// Site Ayarlarını Çek
$stmt = $pdo->query("SELECT * FROM settings");
$site_settings = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $site_settings[$r['setting_key']] = $r['setting_value'];
}
$s_title = !empty($site_settings['site_title']) ? htmlspecialchars($site_settings['site_title']) : 'ORAX';
$s_logo = htmlspecialchars($site_settings['logo'] ?? '');
$s_logo_width = htmlspecialchars($site_settings['logo_width'] ?? '180px');
$s_color = htmlspecialchars($site_settings['primary_color'] ?? '#D32F2F');

// Handle POST requests for Login/Register
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];

    if ($action == 'register') {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $gender = $_POST['gender'];

        if (strlen($password) < 6) {
            $error = 'Şifre en az 6 karakter olmalıdır.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, gender) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password, $gender]);
                $success = 'Kayıt başarılı! Giriş yapabilirsiniz.';
            } catch (PDOException $e) {
                $error = 'Bu kullanıcı adı veya e-posta zaten kullanımda.';
            }
        }
    } elseif ($action == 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Geçersiz e-posta veya şifre.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORAX - Premium Access</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-red: <?php echo $s_color; ?>;
            --accent-red: #FF5252;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        body {
            background-color: #1e1e1e !important;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        .standalone-auth-wrapper {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: radial-gradient(circle at center, rgba(211, 47, 47, 0.05) 0%, rgba(30, 30, 30, 1) 70%);
        }

        .auth-container {
            width: 100%;
            max-width: 450px;
            z-index: 10;
            position: relative;
        }

        .auth-box {
            background: rgba(30, 30, 30, 0.95);
            padding: 3rem;
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(30px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
            width: 100%;
            position: relative;
        }

        .logo-top {
            position: absolute;
            top: -100px;
            left: 50%;
            transform: translateX(-50%);
            text-decoration: none;
            transition: var(--transition);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .logo-top .text-logo {
            font-size: 3rem;
            font-weight: 900;
            color: white;
            letter-spacing: 5px;
            text-shadow: 0 5px 25px rgba(0, 0, 0, 0.3);
        }

        .logo-top .text-logo b { color: var(--primary-red); }

        .logo-top img {
            width: <?php echo $s_logo_width; ?>;
            max-width: 280px;
            max-height: 80px;
            object-fit: contain;
            filter: drop-shadow(0 5px 15px rgba(0,0,0,0.3));
        }

        .auth-form {
            display: none;
            width: 100%;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .auth-form.active {
            display: block;
            opacity: 1;
            animation: fadeInSimple 0.4s forwards;
        }

        @keyframes fadeInSimple {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-form h2 {
            font-size: 2.2rem;
            margin-bottom: 2rem;
            color: white;
            text-align: center;
            font-weight: 800;
        }

        .input-group {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            padding: 0 1.2rem;
            transition: 0.3s;
        }

        .input-group:focus-within {
            border-color: var(--primary-red);
            background: rgba(255, 255, 255, 0.07);
            box-shadow: 0 0 15px rgba(211, 47, 47, 0.1);
        }

        .input-group i { color: var(--primary-red); opacity: 0.8; width: 20px; font-size: 1.1rem; }
        .input-group input, .input-group select {
            background: none;
            border: none;
            color: white;
            padding: 1.2rem;
            flex: 1;
            outline: none;
            font-size: 1rem;
            width: 100%;
            cursor: pointer;
        }

        .input-group select option {
            background-color: #2b2b2b;
            color: white;
            padding: 1rem;
        }

        /* Custom Dropdown Arrow for modern look */
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        .input-group.select-group::after {
            content: "\f078";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            color: var(--primary-red);
            position: absolute;
            right: 1.2rem;
            pointer-events: none;
            font-size: 0.8rem;
            opacity: 0.7;
        }

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
        }
        .password-toggle:hover {
            opacity: 1;
            color: var(--primary-red);
        }

        .btn-primary {
            background: var(--primary-red);
            color: white;
            border: none;
            padding: 1.2rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
            margin-top: 1rem;
            box-shadow: 0 4px 15px rgba(211, 47, 47, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .btn-primary:hover {
            background: var(--accent-red);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(211, 47, 47, 0.5);
        }

        .auth-switch { text-align: center; margin-top: 2rem; color: rgba(255,255,255,0.6); }
        .auth-switch a { color: var(--primary-red); text-decoration: none; font-weight: 700; margin-left: 5px; }
        
        .alert {
            padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; text-align: center; font-size: 0.9rem;
        }
        .alert-danger { background: rgba(211, 47, 47, 0.1); color: #ff5252; border: 1px solid rgba(211, 47, 47, 0.2); }
        .alert-success { background: rgba(76, 175, 80, 0.1); color: #81c784; border: 1px solid rgba(76, 175, 80, 0.2); }

        @media (max-width: 768px) {
            .auth-container {
                padding: 0 20px;
                max-width: 100%;
                margin-top: 40px;
            }
            .auth-box {
                padding: 2.5rem 1.7rem;
            }
            .logo-top {
                font-size: 2.5rem;
                top: -70px;
            }
        }
    </style>
</head>
<body>

<div class="standalone-auth-wrapper">
    <div class="auth-container">
        <a href="index.php" class="logo-top">
            <?php if($s_logo && file_exists($s_logo)): ?>
                <img src="<?php echo $s_logo; ?>" alt="Logo">
            <?php else: ?>
                <div class="text-logo">
                    <?php 
                    $title_parts = explode(' ', $s_title);
                    $first_part = $title_parts[0];
                    if(strlen($first_part) > 1) {
                        echo substr($first_part, 0, -1) . '<b>' . substr($first_part, -1) . '</b>';
                    } else {
                        echo '<b>' . $first_part . '</b>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </a>
        
        <div class="auth-box animate-fade">
            <!-- Login Form -->
            <form id="login-form" method="POST" class="auth-form active">
                <input type="hidden" name="action" value="login">
                <h2>Giriş Yap</h2>
                <?php if($error && $_POST['action']=='login'): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="E-posta Adresi" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="login-pass" placeholder="Şifre" required>
                    <i class="fas fa-eye password-toggle" data-target="login-pass"></i>
                </div>
                <button type="submit" class="btn-primary">Giriş Yap</button>
                <p class="auth-switch">Hesabın yok mu? <a href="#" id="show-register">Kayıt Ol</a></p>
            </form>

            <!-- Register Form -->
            <form id="register-form" method="POST" class="auth-form">
                <input type="hidden" name="action" value="register">
                <h2>Kayıt Ol</h2>
                <?php if($error && $_POST['action']=='register'): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Kullanıcı Adı" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="E-posta" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="reg-pass" placeholder="Şifre (Min. 6 Karakter)" required minlength="6">
                    <i class="fas fa-eye password-toggle" data-target="reg-pass"></i>
                </div>
                <div class="input-group select-group">
                    <i class="fas fa-venus-mars"></i>
                    <select name="gender" required>
                        <option value="" disabled selected>Cinsiyet Seçin</option>
                        <option value="male">Erkek</option>
                        <option value="female">Kadın</option>
                        <option value="other">Diğer</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Kayıt Ol</button>
                <p class="auth-switch">Zaten üye misin? <a href="#" id="show-login">Giriş Yap</a></p>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const showRegister = document.getElementById('show-register');
    const showLogin = document.getElementById('show-login');

    showRegister.addEventListener('click', (e) => {
        e.preventDefault();
        loginForm.classList.remove('active');
        setTimeout(() => {
            registerForm.classList.add('active');
        }, 100);
    });

    showLogin.addEventListener('click', (e) => {
        e.preventDefault();
        registerForm.classList.remove('active');
        setTimeout(() => {
            loginForm.classList.add('active');
        }, 100);
    });

    // Password Toggle Logic
    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passInput = document.getElementById(targetId);
            
            if (passInput.type === 'password') {
                passInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    });

    <?php if(isset($_POST['action']) && $_POST['action'] == 'register'): ?>
        loginForm.classList.remove('active');
        registerForm.classList.add('active');
    <?php endif; ?>
});
</script>

</body>
</html>
