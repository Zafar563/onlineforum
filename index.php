<?php
$pageTitle = "Bosh Sahifa";
require_once __DIR__ . '/header.php';

// Safe variables if DB connection failed
$totalUsers = 0;
$totalTopics = 0;
$totalPosts = 0;
$categories = [];
$latestTopics = [];
$searchResults = [];
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($pdo) {
    try {
        // Fetch general stats
        $totalUsers = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
        $totalTopics = $pdo->query("SELECT COUNT(*) FROM `topics`")->fetchColumn();
        $totalPosts = $pdo->query("SELECT COUNT(*) FROM `posts`")->fetchColumn();
        
        // Fetch all categories with topic and post counts
        $categories = $pdo->query("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM `topics` t WHERE t.category_id = c.id) as topics_count,
                   (SELECT COUNT(*) FROM `posts` p JOIN `topics` t ON p.topic_id = t.id WHERE t.category_id = c.id) as posts_count
            FROM `categories` c
            ORDER BY c.id ASC
        ")->fetchAll();
        
        // Fetch latest topics for sidebar
        $latestTopics = $pdo->query("
            SELECT t.id, t.title, t.created_at, u.username, c.name as category_name
            FROM `topics` t
            JOIN `users` u ON t.user_id = u.id
            JOIN `categories` c ON t.category_id = c.id
            ORDER BY t.created_at DESC
            LIMIT 5
        ")->fetchAll();
        
        // Perform search if input is provided
        if (!empty($searchQuery)) {
            $stmt = $pdo->prepare("
                SELECT t.*, u.username, u.avatar_color, c.name as category_name,
                       (SELECT COUNT(*) FROM `posts` p WHERE p.topic_id = t.id) as posts_count
                FROM `topics` t
                JOIN `users` u ON t.user_id = u.id
                JOIN `categories` c ON t.category_id = c.id
                WHERE t.title LIKE :search OR t.content LIKE :search
                ORDER BY t.created_at DESC
            ");
            $stmt->execute(['search' => "%$searchQuery%"]);
            $searchResults = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $dbError = "Ma'lumotlar bazasidan ma'lumotlarni o'qishda xato: " . $e->getMessage();
    }
} else {
    $dbError = "Ma'lumotlar bazasiga ulanib bo'lmadi. Iltimos, <a href='setup.php' style='color: var(--accent-purple); text-decoration: underline;'>sozlash sahifasini (setup.php)</a> ishga tushiring.";
}
?>

<!-- Hero / Welcome Section -->
<?php if (empty($searchQuery)): ?>
<section class="hero-section animate-fade-in">
    <div class="hero-content">
        <h2 class="hero-title">O'zbekiston Premium Forumiga Xush Kelibsiz!</h2>
        <p class="hero-subtitle">
            Bu yerda siz o'zingizni qiziqtirgan savollarni berishingiz, tajriba almashishingiz va o'xshash qiziqishga ega insonlar bilan muloqot qilishingiz mumkin.
        </p>
        <?php if ($currentUser): ?>
            <a href="create-topic.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Yangi Mavzu Yaratish</a>
        <?php else: ?>
            <a href="register.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Forumga Qo'shilish</a>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- Main Wrapper -->
<div class="main-wrapper">
    
    <?php if (isset($dbError)): ?>
        <div class="glass-panel" style="padding: 24px; text-align: center; border-color: var(--error); margin-bottom: 30px;">
            <i class="bi bi-exclamation-octagon-fill" style="color: var(--error); font-size: 2.5rem; display: block; margin-bottom: 12px;"></i>
            <p><?php echo $dbError; ?></p>
        </div>
    <?php endif; ?>

    <div class="layout-grid">
        <!-- Feed Area -->
        <main>
            <?php if (!empty($searchQuery)): ?>
                <!-- Search Results Section -->
                <div class="topic-list-header">
                    <h3 class="section-title"><i class="bi bi-search"></i> Qidiruv natijalari: "<?php echo esc($searchQuery); ?>"</h3>
                    <a href="index.php" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Barcha Kategoriyalar</a>
                </div>
                
                <?php if (empty($searchResults)): ?>
                    <div class="glass-panel animate-fade-in" style="padding: 40px; text-align: center;">
                        <i class="bi bi-search-heart" style="font-size: 3rem; color: var(--text-muted); display: block; margin-bottom: 15px;"></i>
                        <h4>Hech qanday mavzu topilmadi</h4>
                        <p style="color: var(--text-secondary); margin-top: 8px;">Boshqa kalit so'zlar yordamida qidirib ko'ring yoki bosh sahifaga qayting.</p>
                    </div>
                <?php else: ?>
                    <div class="animate-fade-in">
                        <?php foreach ($searchResults as $topic): ?>
                            <div class="glass-panel topic-row">
                                <div class="topic-main">
                                    <h4 class="topic-title"><a href="topic.php?id=<?php echo $topic['id']; ?>"><?php echo esc($topic['title']); ?></a></h4>
                                    <div class="topic-meta">
                                        <span class="role-badge role-user" style="font-size: 0.7rem;"><?php echo esc($topic['category_name']); ?></span>
                                        <span class="topic-meta-user">
                                            <i class="bi bi-person"></i> <?php echo esc($topic['username']); ?>
                                        </span>
                                        <span><i class="bi bi-clock"></i> <?php echo time_ago($topic['created_at']); ?></span>
                                    </div>
                                </div>
                                <div class="topic-stats-cols">
                                    <div class="topic-stat-col">
                                        <div class="topic-stat-num"><?php echo (int)$topic['views']; ?></div>
                                        <div class="topic-stat-lbl">Ko'rishlar</div>
                                    </div>
                                    <div class="topic-stat-col">
                                        <div class="topic-stat-num"><?php echo (int)$topic['posts_count']; ?></div>
                                        <div class="topic-stat-lbl">Izohlar</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Standard Categories List -->
                <h3 class="section-title"><i class="bi bi-grid-fill"></i> Mavjud Kategoriyalar</h3>
                
                <div class="categories-list">
                    <?php if (empty($categories) && !isset($dbError)): ?>
                        <div class="glass-panel" style="padding: 30px; text-align: center;">
                            <p>Kategoriyalar mavjud emas. Iltimos database setup.php ni yuklang.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($categories as $index => $cat): ?>
                            <div class="glass-panel category-card animate-fade-in" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                                <div class="category-icon-wrapper">
                                    <i class="bi bi-<?php echo esc($cat['icon']); ?>"></i>
                                </div>
                                <div class="category-details">
                                    <h4 class="category-name"><a href="category.php?id=<?php echo $cat['id']; ?>"><?php echo esc($cat['name']); ?></a></h4>
                                    <p class="category-desc"><?php echo esc($cat['description']); ?></p>
                                    <div class="category-stats">
                                        <span><i class="bi bi-file-earmark-text"></i> <b><?php echo (int)$cat['topics_count']; ?></b> mavzu</span>
                                        <span><i class="bi bi-chat-left-dots"></i> <b><?php echo (int)$cat['posts_count']; ?></b> izoh</span>
                                    </div>
                                </div>
                                <div>
                                    <a href="category.php?id=<?php echo $cat['id']; ?>" class="btn btn-secondary btn-sm"><i class="bi bi-chevron-right"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>

        <!-- Sidebar Section -->
        <aside>
            <!-- Create topic CTA in sidebar -->
            <?php if ($currentUser && empty($searchQuery)): ?>
                <div class="glass-panel" style="padding: 24px; text-align: center; margin-bottom: 30px; border-color: rgba(99, 102, 241, 0.25);">
                    <h4 style="margin-bottom: 10px;">Fikr va G'oyalar bormi?</h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 16px;">
                        Kategoriyalarni tanlang va o'zingizning qiziqarli mavzuingizni foruma qo'shing.
                    </p>
                    <a href="create-topic.php" class="btn btn-primary btn-sm" style="width: 100%;"><i class="bi bi-plus-circle"></i> Yangi Mavzu Ochish</a>
                </div>
            <?php endif; ?>

            <!-- Forum Stats Widget -->
            <div class="glass-panel sidebar-widget">
                <h4 class="sidebar-widget-title"><i class="bi bi-bar-chart-line-fill" style="color: var(--accent-indigo);"></i> Forum Statistikasi</h4>
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $totalUsers; ?></div>
                        <div class="stat-label">A'zolar</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $totalTopics; ?></div>
                        <div class="stat-label">Mavzular</div>
                    </div>
                    <div class="stat-box" style="grid-column: span 2;">
                        <div class="stat-value"><?php echo $totalPosts; ?></div>
                        <div class="stat-label">Jami Izohlar</div>
                    </div>
                </div>
            </div>

            <!-- Latest Topics Widget -->
            <div class="glass-panel sidebar-widget">
                <h4 class="sidebar-widget-title"><i class="bi bi-activity" style="color: var(--accent-purple);"></i> Oxirgi Mavzular</h4>
                <?php if (empty($latestTopics)): ?>
                    <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center;">Hozircha mavzular yo'q.</p>
                <?php else: ?>
                    <?php foreach ($latestTopics as $lt): ?>
                        <div class="latest-topic-item">
                            <div class="avatar avatar-sm" style="background-color: var(--accent-indigo); font-size: 0.75rem;">
                                <?php echo esc(strtoupper(substr($lt['username'], 0, 1))); ?>
                            </div>
                            <div>
                                <h5 class="latest-topic-title"><a href="topic.php?id=<?php echo $lt['id']; ?>"><?php echo esc($lt['title']); ?></a></h5>
                                <div class="latest-topic-meta">
                                    <span><?php echo esc($lt['username']); ?></span> &bull; 
                                    <span><?php echo time_ago($lt['created_at']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
