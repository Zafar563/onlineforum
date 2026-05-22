<?php
require_once __DIR__ . '/config.php';

$catId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($catId <= 0) {
    header('Location: index.php');
    exit;
}

$category = null;
$topics = [];

if ($pdo) {
    try {
        // Fetch category info
        $stmt = $pdo->prepare("SELECT * FROM `categories` WHERE `id` = ?");
        $stmt->execute([$catId]);
        $category = $stmt->fetch();
        
        if ($category) {
            // Fetch topics under this category
            $topicsStmt = $pdo->prepare("
                SELECT t.*, u.username, u.avatar_color,
                       (SELECT COUNT(*) FROM `posts` p WHERE p.topic_id = t.id) as posts_count
                FROM `topics` t
                JOIN `users` u ON t.user_id = u.id
                WHERE t.category_id = ?
                ORDER BY t.created_at DESC
            ");
            $topicsStmt->execute([$catId]);
            $topics = $topicsStmt->fetchAll();
        }
    } catch (PDOException $e) {
        $dbError = "Tizim xatoligi yuz berdi: " . $e->getMessage();
    }
}

$pageTitle = $category ? $category['name'] : "Kategoriya Topilmadi";
require_once __DIR__ . '/header.php';
?>

<div class="main-wrapper" style="margin-top: 40px;">
    <?php if (!$category): ?>
        <!-- Category not found -->
        <div class="glass-panel" style="padding: 40px; text-align: center; border-color: var(--error);">
            <i class="bi bi-folder-x" style="font-size: 3rem; color: var(--error); display: block; margin-bottom: 15px;"></i>
            <h3>Kategoriya topilmadi</h3>
            <p style="color: var(--text-secondary); margin-top: 10px;">So'ralgan kategoriya bazada mavjud emas yoki o'chirilgan.</p>
            <a href="index.php" class="btn btn-secondary btn-sm" style="margin-top: 20px;"><i class="bi bi-arrow-left"></i> Bosh Sahifaga Qaytish</a>
        </div>
    <?php else: ?>
        <!-- Back navigation and Category header -->
        <div style="margin-bottom: 30px;">
            <a href="index.php" style="font-size: 0.9rem; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 6px; margin-bottom: 15px;">
                <i class="bi bi-arrow-left"></i> Bosh sahifa
            </a>
            
            <div class="glass-panel animate-fade-in" style="padding: 30px; display: flex; align-items: center; gap: 20px; border-left: 5px solid var(--accent-indigo);">
                <div class="category-icon-wrapper" style="width: 60px; height: 60px; font-size: 1.8rem; background: rgba(99, 102, 241, 0.15);">
                    <i class="bi bi-<?php echo esc($category['icon']); ?>"></i>
                </div>
                <div>
                    <h2 style="font-size: 1.8rem; margin-bottom: 5px;"><?php echo esc($category['name']); ?></h2>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin: 0;"><?php echo esc($category['description']); ?></p>
                </div>
            </div>
        </div>

        <div class="layout-grid">
            <!-- Topics Feed -->
            <main>
                <div class="topic-list-header">
                    <h3 class="section-title"><i class="bi bi-chat-text-fill"></i> Mavzular</h3>
                    <?php if ($currentUser): ?>
                        <a href="create-topic.php?category_id=<?php echo $category['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle"></i> Yangi Mavzu
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (empty($topics)): ?>
                    <!-- No topics yet -->
                    <div class="glass-panel animate-fade-in" style="padding: 50px; text-align: center;">
                        <i class="bi bi-chat-dots-fill" style="font-size: 3rem; color: var(--text-muted); display: block; margin-bottom: 15px;"></i>
                        <h4>Hozircha mavzular yo'q</h4>
                        <p style="color: var(--text-secondary); margin-top: 8px;">Ushbu kategoriyada hali birorta ham mavzu ochilmagan.</p>
                        <?php if ($currentUser): ?>
                            <a href="create-topic.php?category_id=<?php echo $category['id']; ?>" class="btn btn-primary btn-sm" style="margin-top: 20px;">
                                <i class="bi bi-plus-circle"></i> Birinchi bo'lib mavzu yarating!
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-secondary btn-sm" style="margin-top: 20px;">
                                <i class="bi bi-box-arrow-in-right"></i> Mavzu yaratish uchun kiring
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Topics List -->
                    <div class="animate-fade-in">
                        <?php foreach ($topics as $index => $topic): ?>
                            <div class="glass-panel topic-row" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                                <div class="topic-main">
                                    <h4 class="topic-title">
                                        <a href="topic.php?id=<?php echo $topic['id']; ?>"><?php echo esc($topic['title']); ?></a>
                                    </h4>
                                    <div class="topic-meta">
                                        <span class="topic-meta-user">
                                            <span class="avatar avatar-sm" style="width: 20px; height: 20px; font-size: 0.6rem; background-color: <?php echo esc($topic['avatar_color']); ?>; margin-right: 4px; display: inline-flex;">
                                                <?php echo esc(strtoupper(substr($topic['username'], 0, 1))); ?>
                                            </span>
                                            <?php echo esc($topic['username']); ?>
                                        </span>
                                        <span>&bull;</span>
                                        <span><i class="bi bi-clock-history"></i> <?php echo time_ago($topic['created_at']); ?></span>
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
            </main>

            <!-- Sidebar -->
            <aside>
                <!-- Category statistics widget -->
                <div class="glass-panel sidebar-widget">
                    <h4 class="sidebar-widget-title"><i class="bi bi-info-circle-fill" style="color: var(--accent-indigo);"></i> Kategoriya haqida</h4>
                    <ul style="list-style: none; padding: 0; color: var(--text-secondary); font-size: 0.9rem; display: flex; flex-direction: column; gap: 12px;">
                        <li style="display:flex; justify-content:space-between;">
                            <span>Mavzular soni:</span>
                            <strong style="color: var(--text-primary);"><?php echo count($topics); ?></strong>
                        </li>
                        <li style="display:flex; justify-content:space-between;">
                            <span>Jami izohlar:</span>
                            <strong style="color: var(--text-primary);">
                                <?php 
                                $totalComments = 0;
                                foreach ($topics as $t) { $totalComments += $t['posts_count']; }
                                echo $totalComments;
                                ?>
                            </strong>
                        </li>
                        <li style="display:flex; justify-content:space-between;">
                            <span>Kategoriya ID:</span>
                            <strong style="color: var(--text-primary);">#<?php echo $category['id']; ?></strong>
                        </li>
                    </ul>
                </div>

                <!-- Guidelines card -->
                <div class="glass-panel" style="padding: 24px;">
                    <h4 style="font-size: 1.1rem; margin-bottom: 12px;"><i class="bi bi-shield-check" style="color: var(--success);"></i> Forum qoidalari</h4>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.5; margin-bottom: 10px;">
                        Mavzu ochishdan avval avvalgi mavzularni tekshirib ko'ring. Takroriy mavzular moderatorlar tomonidan tahrirlanishi yoki o'chirilishi mumkin.
                    </p>
                    <p style="color: var(--text-secondary); font-size: 0.85rem; line-height: 1.5; margin: 0;">
                        Do'stona muloqot va o'zaro hurmatni saqlashga intiling.
                    </p>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
