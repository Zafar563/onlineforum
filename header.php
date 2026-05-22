<?php
require_once __DIR__ . '/config.php';
$currentUser = current_user();
$searchVal = isset($_GET['search']) ? trim($_GET['search']) : '';
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? esc($pageTitle) . " | Antigravity Forum" : "Antigravity Forum - Fikrlar va Tajribalar Burchagi"; ?></title>
    
    <!-- Meta Tags for SEO -->
    <meta name="description" content="Antigravity Forum - Dasturlash, Sun'iy Intellekt, Kiberxavfsizlik va zamonaviy texnologiyalar haqida do'stona va professional fikr almashish maydoni.">
    <meta name="keywords" content="forum, uzbek forum, dasturlash, kiberxavfsizlik, sun'iy intellekt, ui/ux dizayn, php, mysql">
    
    <!-- Google Fonts & Bootstrap Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Quill.js Editor Snow Theme (Only on pages where needed, or loaded globally) -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
    
    <!-- Main Style Sheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar" id="mainNavbar">
    <div class="navbar-container">
        <!-- Logo -->
        <a href="index.php" class="navbar-brand" id="navLogo">
            <i class="bi bi-chat-square-text-fill"></i> ANTIGRAVITY
        </a>
        
        <!-- Search bar -->
        <form action="index.php" method="GET" class="search-bar" id="searchForm">
            <i class="bi bi-search"></i>
            <input type="text" name="search" placeholder="Mavzularni qidirish..." value="<?php echo esc($searchVal); ?>" autocomplete="off" id="searchInput">
        </form>
        
        <!-- Navigation Links / User profile -->
        <div class="nav-links" id="navLinks">
            <?php if ($currentUser): ?>
                <!-- Registered User Pane -->
                <div class="nav-user" id="navUserMenu">
                    <div class="avatar avatar-sm" style="background-color: <?php echo esc($currentUser['avatar_color']); ?>">
                        <?php echo esc(strtoupper(substr($currentUser['username'], 0, 1))); ?>
                    </div>
                    <div class="nav-user-info">
                        <div class="nav-user-name"><?php echo esc($currentUser['username']); ?></div>
                        <div class="nav-user-role">
                            <?php if ($currentUser['role'] === 'admin'): ?>
                                <span class="role-badge role-admin" style="font-size: 0.6rem; padding: 1px 6px;">Admin</span>
                            <?php elseif ($currentUser['role'] === 'moderator'): ?>
                                <span class="role-badge role-moderator" style="font-size: 0.6rem; padding: 1px 6px;">Mod</span>
                            <?php else: ?>
                                <span class="role-badge role-user" style="font-size: 0.6rem; padding: 1px 6px;">A'zo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Dropdown -->
                    <div class="nav-dropdown" id="navDropdownMenu">
                        <a href="profile.php"><i class="bi bi-person-bounding-box"></i> Profilim</a>
                        <?php if (has_role('admin')): ?>
                            <a href="admin.php"><i class="bi bi-speedometer2"></i> Admin Panel</a>
                        <?php endif; ?>
                        <div style="border-top: 1px solid var(--glass-border); margin: 5px 0;"></div>
                        <a href="logout.php" style="color: var(--error);"><i class="bi bi-box-arrow-right"></i> Chiqish</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Guests pane -->
                <a href="login.php" class="btn btn-secondary btn-sm" id="btnLogin"><i class="bi bi-box-arrow-in-right"></i> Kirish</a>
                <a href="register.php" class="btn btn-primary btn-sm" id="btnRegister"><i class="bi bi-person-plus"></i> Ro'yxatdan o'tish</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Container for Toast Messages -->
<div class="toast-container" id="toastContainer"></div>
