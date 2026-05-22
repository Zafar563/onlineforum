<?php
require_once __DIR__ . '/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$currentUser = current_user();
$error = '';
$success = '';
$userStats = ['topics_count' => 0, 'posts_count' => 0];
$userTopics = [];
$userPosts = [];

if ($pdo) {
    // 1. Fetch statistics
    try {
        $stmtTopicsCount = $pdo->prepare("SELECT COUNT(*) FROM `topics` WHERE `user_id` = ?");
        $stmtTopicsCount->execute([$currentUser['id']]);
        $userStats['topics_count'] = (int)$stmtTopicsCount->fetchColumn();
        
        $stmtPostsCount = $pdo->prepare("SELECT COUNT(*) FROM `posts` WHERE `user_id` = ?");
        $stmtPostsCount->execute([$currentUser['id']]);
        $userStats['posts_count'] = (int)$stmtPostsCount->fetchColumn();
        
        // Fetch current user details from DB to get the latest bio and avatar_color
        $stmtUser = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
        $stmtUser->execute([$currentUser['id']]);
        $dbUser = $stmtUser->fetch();
        
        // 2. Fetch user's latest topics
        $stmtLatestTopics = $pdo->prepare("
            SELECT t.*, c.name as category_name 
            FROM `topics` t 
            JOIN `categories` c ON t.category_id = c.id
            WHERE t.user_id = ? 
            ORDER BY t.created_at DESC 
            LIMIT 5
        ");
        $stmtLatestTopics->execute([$currentUser['id']]);
        $userTopics = $stmtLatestTopics->fetchAll();
        
        // 3. Fetch user's latest posts (comments)
        $stmtLatestPosts = $pdo->prepare("
            SELECT p.*, t.title as topic_title 
            FROM `posts` p 
            JOIN `topics` t ON p.topic_id = t.id 
            WHERE p.user_id = ? 
            ORDER BY p.created_at DESC 
            LIMIT 5
        ");
        $stmtLatestPosts->execute([$currentUser['id']]);
        $userPosts = $stmtLatestPosts->fetchAll();
        
    } catch (PDOException $e) {
        $error = "Tizim xatoligi: " . $e->getMessage();
    }
}

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    $avatarColor = isset($_POST['avatar_color']) ? trim($_POST['avatar_color']) : '';
    
    // Basic verification
    $validColors = ['#6366f1', '#a855f7', '#ec4899', '#10b981', '#0ea5e9', '#f59e0b'];
    if (!in_array($avatarColor, $validColors)) {
        $avatarColor = $dbUser['avatar_color']; // default back
    }
    
    try {
        $update = $pdo->prepare("UPDATE `users` SET `bio` = ?, `avatar_color` = ? WHERE `id` = ?");
        $update->execute([$bio, $avatarColor, $currentUser['id']]);
        
        // Update session so Navbar updates instantly
        $_SESSION['avatar_color'] = $avatarColor;
        
        // Refresh local variables
        $dbUser['bio'] = $bio;
        $dbUser['avatar_color'] = $avatarColor;
        $currentUser['avatar_color'] = $avatarColor;
        
        $success = "Profilingiz muvaffaqiyatli yangilandi!";
    } catch (PDOException $e) {
        $error = "Yangilashda xatolik yuz berdi: " . $e->getMessage();
    }
}

$pageTitle = "Profil: " . $dbUser['username'];
require_once __DIR__ . '/header.php';
?>

<div class="main-wrapper" style="margin-top: 40px;">
    <!-- Profile Header Card -->
    <div class="glass-panel profile-header-card animate-fade-in">
        <div class="avatar avatar-lg" id="previewAvatar" style="background-color: <?php echo esc($dbUser['avatar_color']); ?>;">
            <?php echo esc(strtoupper(substr($dbUser['username'], 0, 1))); ?>
        </div>
        <div class="profile-meta-pane">
            <div class="profile-username-row">
                <h2 class="profile-fullname"><?php echo esc($dbUser['username']); ?></h2>
                <div>
                    <?php if ($dbUser['role'] === 'admin'): ?>
                        <span class="role-badge role-admin">Bosh Admin</span>
                    <?php elseif ($dbUser['role'] === 'moderator'): ?>
                        <span class="role-badge role-moderator">Moderator</span>
                    <?php else: ?>
                        <span class="role-badge role-user">Forum A'zosi</span>
                    <?php endif; ?>
                </div>
            </div>
            <p class="profile-bio"><?php echo $dbUser['bio'] ? esc($dbUser['bio']) : "Foydalanuvchi o'zi haqida hech narsa yozmagan."; ?></p>
            <div class="profile-stats-row">
                <span><i class="bi bi-file-earmark-text"></i> Yaratgan mavzulari: <strong><?php echo $userStats['topics_count']; ?></strong></span>
                <span><i class="bi bi-chat-left-text"></i> Qoldirgan izohlari: <strong><?php echo $userStats['posts_count']; ?></strong></span>
                <span><i class="bi bi-calendar3"></i> Ro'yxatdan o'tdi: <strong><?php echo date('d.m.Y', strtotime($dbUser['created_at'])); ?></strong></span>
            </div>
        </div>
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
        <!-- Settings Form & activity lists -->
        <main>
            <!-- Tabs / Segmented controller style -->
            <div class="topic-list-header">
                <h3 class="section-title"><i class="bi bi-activity"></i> So'nggi Faolliklar</h3>
            </div>

            <!-- Latest Topics list -->
            <div class="glass-panel" style="padding: 24px; margin-bottom: 30px;">
                <h4 style="font-size: 1.15rem; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
                    <i class="bi bi-file-earmark-text-fill" style="color: var(--accent-indigo);"></i> Mualliflik qilgan so'nggi mavzulari
                </h4>
                <?php if (empty($userTopics)): ?>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Siz hali birorta ham mavzu yaratmagansiz.</p>
                <?php else: ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($userTopics as $ut): ?>
                            <li style="padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.04); display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <a href="topic.php?id=<?php echo $ut['id']; ?>" style="font-weight: 500; color: var(--text-primary);"><?php echo esc($ut['title']); ?></a>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                                        Kategoriya: <?php echo esc($ut['category_name']); ?> &bull; <?php echo time_ago($ut['created_at']); ?>
                                    </div>
                                </div>
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><i class="bi bi-eye"></i> <?php echo $ut['views']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Latest Posts list -->
            <div class="glass-panel" style="padding: 24px;">
                <h4 style="font-size: 1.15rem; margin-bottom: 15px; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px;">
                    <i class="bi bi-chat-left-dots-fill" style="color: var(--accent-purple);"></i> Qoldirgan oxirgi izohlari
                </h4>
                <?php if (empty($userPosts)): ?>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Siz hali birorta ham izoh yozmagansiz.</p>
                <?php else: ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($userPosts as $up): ?>
                            <li style="padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.04);">
                                <div style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5; margin-bottom: 4px;">
                                    <?php echo strip_tags(substr($up['content'], 0, 100)) . (strlen($up['content']) > 100 ? '...' : ''); ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    Mavzu: <a href="topic.php?id=<?php echo $up['topic_id']; ?>" style="color: var(--text-secondary); text-decoration: underline;"><?php echo esc($up['topic_title']); ?></a> &bull; <?php echo time_ago($up['created_at']); ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </main>

        <!-- Profile Settings Sidebar -->
        <aside>
            <div class="glass-panel sidebar-widget">
                <h4 class="sidebar-widget-title"><i class="bi bi-gear-fill" style="color: var(--accent-indigo);"></i> Profilni Tahrirlash</h4>
                
                <form action="profile.php" method="POST">
                    <!-- Bio Input -->
                    <div class="form-group">
                        <label for="bio" class="form-label">Siz haqingizda (Bio)</label>
                        <textarea name="bio" id="bio" class="form-control" placeholder="O'zingiz haqingizda qisqacha ma'lumot kiriting..." maxlength="250" style="min-height: 100px;"><?php echo esc($dbUser['bio']); ?></textarea>
                    </div>

                    <!-- Avatar Color Picker -->
                    <div class="form-group">
                        <label class="form-label">Profil Rangingiz (Avatar)</label>
                        <div class="color-picker-grid">
                            <?php 
                            $avatarColors = ['#6366f1', '#a855f7', '#ec4899', '#10b981', '#0ea5e9', '#f59e0b'];
                            foreach ($avatarColors as $color): 
                                $activeClass = ($color === $dbUser['avatar_color']) ? 'active' : '';
                            ?>
                                <div class="color-swatch <?php echo $activeClass; ?>" 
                                     data-color="<?php echo $color; ?>" 
                                     style="background-color: <?php echo $color; ?>;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="avatar_color" id="avatarColorInput" value="<?php echo esc($dbUser['avatar_color']); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%; margin-top: 10px;">
                        O'zgarishlarni saqlash <i class="bi bi-shield-check"></i>
                    </button>
                </form>
            </div>
            
            <div class="glass-panel" style="padding: 20px; text-align: center; border-color: rgba(244,63,94,0.2);">
                <h4 style="font-size: 1rem; color: var(--error); margin-bottom: 8px;"><i class="bi bi-box-arrow-right"></i> Hisobdan chiqish</h4>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 15px;">Seansni yopish va forumdan chiqish.</p>
                <a href="logout.php" class="btn btn-outline btn-sm" style="border-color: var(--error); color: var(--error); width: 100%;"><i class="bi bi-power"></i> Chiqish</a>
            </div>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
