<?php
require_once __DIR__ . '/config.php';

// If already logged in, redirect
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$usernameVal = '';
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameVal = isset($_POST['username']) ? trim($_POST['username']) : '';
    $emailVal = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Basic verification
    if (empty($usernameVal) || empty($emailVal) || empty($password) || empty($confirmPassword)) {
        $error = 'Iltimos, barcha maydonlarni to\'ldiring.';
    } elseif (strlen($usernameVal) < 3) {
        $error = 'Foydalanuvchi nomi kamida 3 belgidan iborat bo\'lishi shart.';
    } elseif (!filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email formati noto\'g\'ri.';
    } elseif (strlen($password) < 6) {
        $error = 'Parol uzunligi kamida 6 belgidan iborat bo\'lishi shart.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Parollar bir-biriga mos kelmadi.';
    } else {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = ?");
            $stmt->execute([$usernameVal]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Ushbu foydalanuvchi nomi allaqachon band qilingan.';
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `email` = ?");
                $stmt->execute([$emailVal]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Ushbu email manzili ro\'yxatdan o\'tgan.';
                } else {
                    // Register the user
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Choose random aesthetic color for avatar
                    $avatarColors = ['#6366f1', '#a855f7', '#ec4899', '#10b981', '#0ea5e9', '#f59e0b'];
                    $randomColor = $avatarColors[array_rand($avatarColors)];
                    
                    $insert = $pdo->prepare("
                        INSERT INTO `users` (`username`, `email`, `password_hash`, `avatar_color`, `role`, `bio`) 
                        VALUES (?, ?, ?, ?, 'user', ?)
                    ");
                    $insert->execute([$usernameVal, $emailVal, $passwordHash, $randomColor, 'Salom! Men forumning yangi a\'zosiman.']);
                    
                    // Automatically log in the user after registering
                    $userId = $pdo->lastInsertId();
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $usernameVal;
                    $_SESSION['email'] = $emailVal;
                    $_SESSION['role'] = 'user';
                    $_SESSION['avatar_color'] = $randomColor;
                    
                    header('Location: index.php');
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error = 'Ro\'yxatdan o\'tishda xatolik yuz berdi: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ro'yxatdan o'tish | StackHub</title>
    <!-- Google Fonts & Bootstrap Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body style="display:flex; align-items:center; justify-content:center;">

<div class="auth-wrapper" style="margin-top: 30px; margin-bottom: 30px;">
    <div class="glass-panel auth-box animate-fade-in">
        <div class="auth-logo">
            <a href="index.php" class="navbar-brand" style="font-size: 2.2rem; justify-content: center; display: inline-flex;">
                <i class="bi bi-layers-fill"></i> StackHub
            </a>
        </div>
        
        <h3 class="auth-title">Hisob Yaratish</h3>
        <p class="auth-subtitle">Platformaning to'laqonli a'zosiga aylaning.</p>
        
        <?php if (!empty($error)): ?>
            <div class="auth-alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?php echo esc($error); ?></span>
            </div>
        <?php endif; ?>
        
        <form action="register.php" method="POST" id="registerForm">
            <div class="form-group">
                <label for="username" class="form-label">Foydalanuvchi nomi</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="Kamida 3 ta belgi" value="<?php echo esc($usernameVal); ?>" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email manzil</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="example@forum.uz" value="<?php echo esc($emailVal); ?>" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Maxfiy parol</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Kamida 6 ta belgi" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Parolni tasdiqlash</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Parolni qayta kiriting" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                Ro'yxatdan o'tish <i class="bi bi-person-plus"></i>
            </button>
        </form>
        
        <div class="auth-footer">
            Hisobingiz bormi? <a href="login.php" style="font-weight: 500;">Kirish</a>
        </div>
    </div>
</div>

<?php if (empty($error)): ?>
<!-- Extra script for live password checks or toasts -->
<?php endif; ?>

</body>
</html>
