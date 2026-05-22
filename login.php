<?php
require_once __DIR__ . '/config.php';

// If already logged in, redirect
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$loginVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginVal = isset($_POST['login']) ? trim($_POST['login']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($loginVal) || empty($password)) {
        $error = 'Iltimos, barcha maydonlarni to\'ldiring.';
    } else {
        try {
            // Find user by username or email
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = ? OR `email` = ?");
            $stmt->execute([$loginVal, $loginVal]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Password is correct, setup session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['avatar_color'] = $user['avatar_color'];
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Foydalanuvchi nomi yoki parol xato.';
            }
        } catch (PDOException $e) {
            $error = 'Tizimda xatolik yuz berdi: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirish | Antigravity Forum</title>
    <!-- Google Fonts & Bootstrap Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body style="display:flex; align-items:center; justify-content:center;">

<div class="auth-wrapper">
    <div class="glass-panel auth-box animate-fade-in">
        <div class="auth-logo">
            <a href="index.php" class="navbar-brand" style="font-size: 2.2rem; justify-content: center; display: inline-flex;">
                <i class="bi bi-chat-square-text-fill"></i> ANTIGRAVITY
            </a>
        </div>
        
        <h3 class="auth-title">Xush kelibsiz!</h3>
        <p class="auth-subtitle">Forumga kirish uchun shaxsingizni tasdiqlang.</p>
        
        <?php if (!empty($error)): ?>
            <div class="auth-alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?php echo esc($error); ?></span>
            </div>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="login" class="form-label">Foydalanuvchi nomi yoki Email</label>
                <input type="text" name="login" id="login" class="form-control" placeholder="admin yoki admin@forum.uz" value="<?php echo esc($loginVal); ?>" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Maxfiy parol</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Parolingizni kiriting" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                Tizimga kirish <i class="bi bi-box-arrow-in-right"></i>
            </button>
        </form>
        
        <div class="auth-footer">
            Hisobingiz yo'qmi? <a href="register.php" style="font-weight: 500;">Ro'yxatdan o'ting</a>
        </div>
    </div>
</div>

</body>
</html>
