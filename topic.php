<?php
require_once __DIR__ . '/config.php';

$topicId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($topicId <= 0) {
    header('Location: index.php');
    exit;
}

$topic = null;
$posts = [];
$isLiked = false;
$likesCount = 0;
$currentUser = current_user();

if ($pdo) {
    try {
        // 1. Increment views count
        $updateViews = $pdo->prepare("UPDATE `topics` SET `views` = `views` + 1 WHERE `id` = ?");
        $updateViews->execute([$topicId]);

        // 2. Fetch topic details with category and author info
        $stmt = $pdo->prepare("
            SELECT t.*, u.username, u.avatar_color, u.role, u.bio, c.name as category_name
            FROM `topics` t
            JOIN `users` u ON t.user_id = u.id
            JOIN `categories` c ON t.category_id = c.id
            WHERE t.id = ?
        ");
        $stmt->execute([$topicId]);
        $topic = $stmt->fetch();

        if ($topic) {
            // 3. Fetch likes count
            $likesStmt = $pdo->prepare("SELECT COUNT(*) FROM `likes` WHERE `topic_id` = ?");
            $likesStmt->execute([$topicId]);
            $likesCount = (int)$likesStmt->fetchColumn();

            // 4. Check if current user liked it
            if ($currentUser) {
                $checkLiked = $pdo->prepare("SELECT COUNT(*) FROM `likes` WHERE `user_id` = ? AND `topic_id` = ?");
                $checkLiked->execute([$currentUser['id'], $topicId]);
                $isLiked = $checkLiked->fetchColumn() > 0;
            }

            // 5. Fetch posts (comments) under this topic
            $postsStmt = $pdo->prepare("
                SELECT p.*, u.username, u.avatar_color, u.role
                FROM `posts` p
                JOIN `users` u ON p.user_id = u.id
                WHERE p.topic_id = ?
                ORDER BY p.created_at ASC
            ");
            $postsStmt->execute([$topicId]);
            $posts = $postsStmt->fetchAll();
        }
    } catch (PDOException $e) {
        $dbError = "Tizim xatoligi yuz berdi: " . $e->getMessage();
    }
}

$pageTitle = $topic ? $topic['title'] : "Mavzu Topilmadi";
require_once __DIR__ . '/header.php';
?>

<div class="main-wrapper" style="margin-top: 40px;">
    <?php if (!$topic): ?>
        <!-- Topic Not Found -->
        <div class="glass-panel" style="padding: 40px; text-align: center; border-color: var(--error);">
            <i class="bi bi-file-earmark-x" style="font-size: 3rem; color: var(--error); display: block; margin-bottom: 15px;"></i>
            <h3>Mavzu topilmadi</h3>
            <p style="color: var(--text-secondary); margin-top: 10px;">So'ralgan mavzu mavjud emas yoki o'chirilgan.</p>
            <a href="index.php" class="btn btn-secondary btn-sm" style="margin-top: 20px;"><i class="bi bi-arrow-left"></i> Bosh Sahifaga Qaytish</a>
        </div>
    <?php else: ?>
        <!-- Back Navigation -->
        <div style="margin-bottom: 25px;">
            <a href="category.php?id=<?php echo $topic['category_id']; ?>" style="font-size: 0.9rem; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-arrow-left"></i> <?php echo esc($topic['category_name']); ?> kategoriyasiga qaytish
            </a>
        </div>

        <div class="layout-grid">
            <!-- Topic Body & Comments -->
            <main>
                <!-- 1. Topic Original Card -->
                <article class="topic-view-container">
                    <div class="topic-view-header">
                        <h2 class="topic-view-title"><?php echo esc($topic['title']); ?></h2>
                        <div class="topic-author-pane">
                            <div class="author-details">
                                <div class="avatar" style="background-color: <?php echo esc($topic['avatar_color']); ?>;">
                                    <?php echo esc(strtoupper(substr($topic['username'], 0, 1))); ?>
                                </div>
                                <div>
                                    <div class="author-name"><?php echo esc($topic['username']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        <?php if ($topic['role'] === 'admin'): ?>
                                            <span class="role-badge role-admin" style="font-size: 0.6rem; padding: 1px 6px;">Admin</span>
                                        <?php elseif ($topic['role'] === 'moderator'): ?>
                                            <span class="role-badge role-moderator" style="font-size: 0.6rem; padding: 1px 6px;">Mod</span>
                                        <?php else: ?>
                                            <span class="role-badge role-user" style="font-size: 0.6rem; padding: 1px 6px;">A'zo</span>
                                        <?php endif; ?>
                                        &bull; <?php echo time_ago($topic['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Stats badges -->
                            <div style="display:flex; gap: 15px; color: var(--text-muted); font-size: 0.85rem;">
                                <span><i class="bi bi-eye"></i> <b><?php echo (int)$topic['views']; ?></b> ko'rildi</span>
                                <span><i class="bi bi-chat-left-text"></i> <b><span id="commentsCountLabel"><?php echo count($posts); ?></span></b> izoh</span>
                            </div>
                        </div>
                    </div>

                    <!-- Topic original post content -->
                    <div class="topic-view-content">
                        <?php 
                        // Allow safe basic WYSIWYG tags
                        echo strip_tags($topic['content'], '<strong><em><p><ol><ul><li><a><br>'); 
                        ?>
                    </div>

                    <!-- Like interactive controls -->
                    <div class="topic-actions">
                        <div>
                            <button id="likeButton" class="like-btn <?php echo $isLiked ? 'liked' : ''; ?>" data-topic-id="<?php echo $topic['id']; ?>">
                                <i class="bi <?php echo $isLiked ? 'bi-heart-fill' : 'bi-heart'; ?>"></i> 
                                Foydali deb topildi (<span id="likesCount"><?php echo $likesCount; ?></span>)
                            </button>
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">
                            Mavzu ID: #<?php echo $topic['id']; ?>
                        </div>
                    </div>
                </article>

                <!-- 2. Comments List Pane -->
                <h3 class="comments-section-title"><i class="bi bi-chat-left-dots-fill" style="color: var(--accent-purple);"></i> Izohlar va Javoblar</h3>
                <div id="postsContainer">
                    <?php if (empty($posts)): ?>
                        <div class="glass-panel animate-fade-in" id="noPostsAlert" style="padding: 30px; text-align: center; margin-bottom: 20px;">
                            <i class="bi bi-chat-heart" style="font-size: 2.5rem; color: var(--text-muted); display: block; margin-bottom: 12px;"></i>
                            <p style="color: var(--text-secondary); margin: 0;">Ushbu mavzuda hali hech qanday izoh qoldirilmagan. Birinchi bo'ling!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="glass-panel post-card animate-fade-in" id="post-<?php echo $post['id']; ?>">
                                <div class="post-left">
                                    <div class="avatar" style="background-color: <?php echo esc($post['avatar_color']); ?>;">
                                        <?php echo esc(strtoupper(substr($post['username'], 0, 1))); ?>
                                    </div>
                                    <div class="post-username" title="<?php echo esc($post['username']); ?>"><?php echo esc($post['username']); ?></div>
                                </div>
                                <div class="post-right">
                                    <div class="post-header">
                                        <?php if ($post['role'] === 'admin'): ?>
                                            <span class="role-badge role-admin">Admin</span>
                                        <?php elseif ($post['role'] === 'moderator'): ?>
                                            <span class="role-badge role-moderator">Mod</span>
                                        <?php else: ?>
                                            <span class="role-badge role-user">A'zo</span>
                                        <?php endif; ?>
                                        <div class="post-meta">
                                            <i class="bi bi-clock-history"></i> <?php echo time_ago($post['created_at']); ?>
                                        </div>
                                    </div>
                                    <div class="post-content">
                                        <?php echo strip_tags($post['content'], '<strong><em><p><ol><ul><li><a><br>'); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- 3. Post a Comment Form -->
                <div class="glass-panel comment-form-container">
                    <h4 class="form-title"><i class="bi bi-reply-fill" style="color: var(--accent-indigo);"></i> Izoh Qoldirish</h4>
                    <?php if ($currentUser): ?>
                        <form id="commentForm" data-topic-id="<?php echo $topic['id']; ?>">
                            <div class="editor-container">
                                <!-- Quill WYSWYG Editor -->
                                <div id="editor"></div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                Izohni chop etish <i class="bi bi-send-fill"></i>
                            </button>
                        </form>
                    <?php else: ?>
                        <div style="text-align: center; padding: 15px; background: rgba(15, 23, 42, 0.4); border: 1px solid var(--glass-border); border-radius: var(--radius-md);">
                            <p style="color: var(--text-secondary); margin-bottom: 12px; font-size: 0.95rem;">
                                Mavzuga izoh qoldirish yoki savollarga javob berish uchun tizimga kirishingiz lozim.
                            </p>
                            <a href="login.php" class="btn btn-primary btn-sm"><i class="bi bi-box-arrow-in-right"></i> Kirish</a>
                            <a href="register.php" class="btn btn-secondary btn-sm"><i class="bi bi-person-plus"></i> Ro'yxatdan o'tish</a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>

            <!-- Sidebar -->
            <aside>
                <!-- Topic Author Information Widget -->
                <div class="glass-panel sidebar-widget" style="text-align: center;">
                    <h4 class="sidebar-widget-title" style="text-align: left;"><i class="bi bi-person-circle" style="color: var(--accent-pink);"></i> Muallif Profili</h4>
                    <div class="avatar avatar-lg" style="background-color: <?php echo esc($topic['avatar_color']); ?>; margin: 15px auto 12px;">
                        <?php echo esc(strtoupper(substr($topic['username'], 0, 1))); ?>
                    </div>
                    <h4 style="font-size: 1.25rem; margin-bottom: 4px;"><?php echo esc($topic['username']); ?></h4>
                    <div style="margin-bottom: 12px;">
                        <?php if ($topic['role'] === 'admin'): ?>
                            <span class="role-badge role-admin">Administrator</span>
                        <?php elseif ($topic['role'] === 'moderator'): ?>
                            <span class="role-badge role-moderator">Moderator</span>
                        <?php else: ?>
                            <span class="role-badge role-user">Forum A'zosi</span>
                        <?php endif; ?>
                    </div>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.4; border-top: 1px solid var(--glass-border); padding-top: 15px; margin: 0;">
                        <?php echo $topic['bio'] ? esc($topic['bio']) : "Muallif haqida ma'lumot kiritilmagan."; ?>
                    </p>
                </div>

                <!-- Guidelines -->
                <div class="glass-panel" style="padding: 24px;">
                    <h4 style="font-size: 1.1rem; margin-bottom: 12px;"><i class="bi bi-bookmark-star-fill" style="color: var(--warning);"></i> Maslahat</h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.5; margin: 0;">
                        Yozilgan javobingiz mavzuga to'liq aloqador ekanligiga ishonch hosil qiling. Boshqa foydalanuvchilar vaqtini hurmat qiling.
                    </p>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
