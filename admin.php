<?php
require_once __DIR__ . '/config.php';

// Check authorization (Admin role required)
if (!is_logged_in() || !has_role('admin')) {
    header('Location: index.php');
    exit;
}

$currentUser = current_user();
$error = '';
$success = '';

// Handle Admin Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    
    // Add Category
    if ($action === 'add_category') {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $desc = isset($_POST['description']) ? trim($_POST['description']) : '';
        $icon = isset($_POST['icon']) ? trim($_POST['icon']) : 'chat-left-text';
        
        if (empty($name) || empty($desc)) {
            $error = "Iltimos, kategoriyaning nomi va tavsifini kiriting.";
        } else {
            try {
                $ins = $pdo->prepare("INSERT INTO `categories` (`name`, `description`, `icon`) VALUES (?, ?, ?)");
                $ins->execute([$name, $desc, $icon]);
                $success = "Yangi kategoriya muvaffaqiyatli qo'shildi!";
            } catch (PDOException $e) {
                $error = "Kategoriya qo'shishda xatolik: " . $e->getMessage();
            }
        }
    }
    
    // Delete Category
    if ($action === 'delete_category') {
        $catId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        if ($catId > 0) {
            try {
                $del = $pdo->prepare("DELETE FROM `categories` WHERE `id` = ?");
                $del->execute([$catId]);
                $success = "Kategoriya o'chirib yuborildi.";
            } catch (PDOException $e) {
                $error = "Kategoriyani o'chirishda xatolik: " . $e->getMessage();
            }
        }
    }
    
    // Edit User Role
    if ($action === 'change_role') {
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $newRole = isset($_POST['role']) ? trim($_POST['role']) : 'user';
        
        $validRoles = ['user', 'moderator', 'admin'];
        if ($userId > 0 && in_array($newRole, $validRoles)) {
            // Prevent self-demotion
            if ($userId === $currentUser['id']) {
                $error = "Siz o'z rolingizni o'zgartira olmaysiz.";
            } else {
                try {
                    $up = $pdo->prepare("UPDATE `users` SET `role` = ? WHERE `id` = ?");
                    $up->execute([$newRole, $userId]);
                    $success = "Foydalanuvchi roli muvaffaqiyatli yangilandi.";
                } catch (PDOException $e) {
                    $error = "Foydalanuvchi rolimi o'zgartirishda xatolik: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch all categories
$categories = [];
$users = [];

if ($pdo) {
    try {
        $categories = $pdo->query("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM `topics` t WHERE t.category_id = c.id) as topics_count 
            FROM `categories` c 
            ORDER BY c.id ASC
        ")->fetchAll();
        
        $users = $pdo->query("SELECT * FROM `users` ORDER BY `created_at` DESC")->fetchAll();
    } catch (PDOException $e) {
        $error = "Tizim xatoligi: " . $e->getMessage();
    }
}

$pageTitle = "Admin Dashboard";
require_once __DIR__ . '/header.php';
?>

<div class="main-wrapper" style="margin-top: 40px;">
    <!-- Admin Header Page -->
    <div style="margin-bottom: 30px;">
        <h2 style="font-size: 2.2rem; font-family: var(--font-heading); margin-bottom: 5px;">
            <i class="bi bi-speedometer2" style="background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i> Boshqaruv Paneli (Admin)
        </h2>
        <p style="color: var(--text-secondary); margin: 0;">Forum kategoriyalari va foydalanuvchilar rollarini shu yerdan boshqarasiz.</p>
    </div>

    <?php if (!empty($success)): ?>
        <div class="glass-panel" style="padding: 16px; border-left: 4px solid var(--success); color: var(--success); margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
            <i class="bi bi-check-circle-fill"></i>
            <span><?php echo esc($success); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="auth-alert" style="margin-bottom: 25px;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><?php echo esc($error); ?></span>
        </div>
    <?php endif; ?>

    <div class="layout-grid">
        <!-- Categories & Users Table Area -->
        <main>
            <!-- 1. Categories Management Widget -->
            <div class="glass-panel" style="padding: 24px; margin-bottom: 30px;">
                <h3 style="font-size: 1.25rem; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
                    <i class="bi bi-grid-fill" style="color: var(--accent-indigo);"></i> Kategoriyalar Boshqaruvi
                </h3>
                
                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Kategoriya</th>
                                <th>Mavzular</th>
                                <th style="text-align:right;">Amallar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap: 10px;">
                                            <div class="category-icon-wrapper" style="width:32px; height:32px; font-size: 0.95rem; border-radius: var(--radius-sm);">
                                                <i class="bi bi-<?php echo esc($cat['icon']); ?>"></i>
                                            </div>
                                            <div>
                                                <strong><?php echo esc($cat['name']); ?></strong>
                                                <div style="font-size:0.75rem; color: var(--text-muted);"><?php echo esc(substr($cat['description'], 0, 70)) . (strlen($cat['description']) > 70 ? '...' : ''); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><strong><?php echo $cat['topics_count']; ?></strong> ta</td>
                                    <td style="text-align:right;">
                                        <form action="admin.php" method="POST" onsubmit="return confirm('Kategoriyani va unga tegishli barcha mavzularni butunlay o\'chirmoqchimisiz?');" style="display:inline-block;">
                                            <input type="hidden" name="action" value="delete_category">
                                            <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" style="border-color: var(--error); color: var(--error); padding: 4px 8px;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Users Management Widget -->
            <div class="glass-panel" style="padding: 24px;">
                <h3 style="font-size: 1.25rem; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
                    <i class="bi bi-people-fill" style="color: var(--accent-purple);"></i> Foydalanuvchilar va Rollar Boshqaruvi
                </h3>
                
                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Foydalanuvchi</th>
                                <th>Email</th>
                                <th>Rol</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap: 8px;">
                                            <div class="avatar avatar-sm" style="background-color: <?php echo esc($user['avatar_color']); ?>; width:26px; height:26px; font-size: 0.75rem;">
                                                <?php echo esc(strtoupper(substr($user['username'], 0, 1))); ?>
                                            </div>
                                            <strong><?php echo esc($user['username']); ?></strong>
                                        </div>
                                    </td>
                                    <td><span style="font-size:0.85rem; color: var(--text-secondary);"><?php echo esc($user['email']); ?></span></td>
                                    <td>
                                        <?php if ($user['id'] === $currentUser['id']): ?>
                                            <span class="role-badge role-admin">Asosiy Admin</span>
                                        <?php else: ?>
                                            <!-- Simple role dropdown changer -->
                                            <form action="admin.php" method="POST" style="margin: 0; display:inline-flex;">
                                                <input type="hidden" name="action" value="change_role">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <select name="role" onchange="this.form.submit()" class="form-control" style="padding: 4px 8px; font-size: 0.8rem; background-color: var(--bg-secondary); border-color: var(--glass-border); color: var(--text-primary); border-radius: var(--radius-sm); width: auto;">
                                                    <option value="user" <?php echo ($user['role'] === 'user') ? 'selected' : ''; ?>>A'zo (User)</option>
                                                    <option value="moderator" <?php echo ($user['role'] === 'moderator') ? 'selected' : ''; ?>>Mod (Moderator)</option>
                                                    <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                                </select>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- Sidebar Forms Area -->
        <aside>
            <!-- 3. Add Category Form Widget -->
            <div class="glass-panel sidebar-widget">
                <h4 class="sidebar-widget-title"><i class="bi bi-plus-circle" style="color: var(--accent-indigo);"></i> Yangi Kategoriya</h4>
                
                <form action="admin.php" method="POST">
                    <input type="hidden" name="action" value="add_category">
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Kategoriya Nomi</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Masalan: Mobil Ilovalar" required autocomplete="off">
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Tavsifi (Description)</label>
                        <textarea name="description" id="description" class="form-control" placeholder="Kategoriya haqida qisqacha ma'lumot..." style="min-height: 80px;" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="icon" class="form-label">Belgi (Bootstrap Icon)</label>
                        <select name="icon" id="icon" class="form-control" style="background-color: var(--bg-secondary); color: var(--text-primary);">
                            <option value="chat-left-text">Standart (chat)</option>
                            <option value="code-slash">Dasturlash (code)</option>
                            <option value="cpu">Texnologiyalar (cpu)</option>
                            <option value="shield-lock">Xavfsizlik (shield)</option>
                            <option value="palette">Dizayn (palette)</option>
                            <option value="chat-dots">Muloqot (chat-dots)</option>
                            <option value="laptop">Kompyuterlar (laptop)</option>
                            <option value="book">Kitoblar/Ta'lim (book)</option>
                            <option value="graph-up-arrow">Moliya (graph)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                        Kategoriyani qo'shish <i class="bi bi-plus-circle-fill"></i>
                    </button>
                </form>
            </div>
            
            <div class="glass-panel" style="padding: 20px;">
                <h4 style="font-size: 1.05rem; margin-bottom: 8px;"><i class="bi bi-info-circle-fill" style="color: var(--info);"></i> Tizim ma'lumoti</h4>
                <p style="font-size: 0.82rem; color: var(--text-secondary); line-height: 1.5; margin: 0;">
                    Kategoriyalar ro'yxatidan istalgan kategoriyani o'chirganingizda, o'sha kategoriyaga ulanib ochilgan barcha mavzular va postlar (SQL CASCADE yordamida) avtomatik o'chirib yuboriladi. Ehtiyot bo'ling!
                </p>
            </div>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
