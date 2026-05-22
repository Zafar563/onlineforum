<?php
require_once __DIR__ . '/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$currentUser = current_user();
$categories = [];
$error = '';
$titleVal = '';
$selectedCat = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

if ($pdo) {
    try {
        // Fetch all categories for select options
        $categories = $pdo->query("SELECT * FROM `categories` ORDER BY `id` ASC")->fetchAll();
    } catch (PDOException $e) {
        $error = "Tizim xatoligi: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titleVal = isset($_POST['title']) ? trim($_POST['title']) : '';
    $catId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    
    // Validate Quill content text length
    $cleanContent = strip_tags($content, '<strong><em><p><ol><ul><li><a><br>');
    $cleanContent = trim($cleanContent);
    
    if (empty($titleVal) || $catId <= 0 || empty($cleanContent) || $cleanContent === '<p><br></p>') {
        $error = 'Iltimos, barcha maydonlarni to\'ldiring.';
    } elseif (strlen($titleVal) < 5) {
        $error = 'Mavzu sarlavhasi kamida 5 ta belgidan iborat bo\'lishi kerak.';
    } elseif (strlen(strip_tags($cleanContent)) < 15) {
        $error = 'Mavzu mazmuni kamida 15 ta belgidan iborat bo\'lishi shart.';
    } else {
        try {
            // Save topic
            $stmt = $pdo->prepare("
                INSERT INTO `topics` (`category_id`, `user_id`, `title`, `content`) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$catId, $currentUser['id'], $titleVal, $content]);
            $newTopicId = $pdo->lastInsertId();
            
            header("Location: topic.php?id=" . $newTopicId);
            exit;
        } catch (PDOException $e) {
            $error = 'Mavzu yaratishda xatolik yuz berdi: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Yangi Mavzu Yaratish";
require_once __DIR__ . '/header.php';
?>

<div class="main-wrapper" style="margin-top: 40px;">
    <div style="margin-bottom: 25px;">
        <a href="index.php" style="font-size: 0.9rem; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 6px;">
            <i class="bi bi-arrow-left"></i> Bosh sahifaga qaytish
        </a>
    </div>

    <div class="glass-panel animate-fade-in" style="max-width: 800px; margin: 0 auto; padding: 40px;">
        <h2 style="font-size: 1.8rem; margin-bottom: 8px; font-family: var(--font-heading);"><i class="bi bi-pencil-square" style="color: var(--accent-indigo);"></i> Yangi Mavzu Yaratish</h2>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 30px;">
            Siz ochadigan mavzu barcha foydalanuvchilarga ko'rinadi va muhokamalarga sabab bo'ladi.
        </p>

        <?php if (!empty($error)): ?>
            <div class="auth-alert" style="margin-bottom: 25px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?php echo esc($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="create-topic.php" method="POST" id="createTopicForm">
            <!-- Title -->
            <div class="form-group">
                <label for="title" class="form-label">Mavzu Sarlavhasi</label>
                <input type="text" name="title" id="title" class="form-control" placeholder="Mavzuning qisqa va tushunarli sarlavhasi" value="<?php echo esc($titleVal); ?>" required autocomplete="off">
            </div>

            <!-- Category selection -->
            <div class="form-group">
                <label for="category_id" class="form-label">Forum Kategoriyasi</label>
                <select name="category_id" id="category_id" class="form-control" style="background-color: var(--bg-secondary); color: var(--text-primary);" required>
                    <option value="" disabled <?php echo ($selectedCat === 0) ? 'selected' : ''; ?>>Kategoriyani tanlang...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($selectedCat === (int)$cat['id']) ? 'selected' : ''; ?>>
                            <?php echo esc($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Content Quill Editor -->
            <div class="form-group">
                <label class="form-label">Mavzu Mazmuni</label>
                <div class="editor-container">
                    <div id="editor" style="min-height: 250px;"></div>
                </div>
                <input type="hidden" name="content" id="hiddenContent">
            </div>

            <!-- Action buttons -->
            <div style="display:flex; justify-content: flex-end; gap: 15px; margin-top: 25px;">
                <a href="index.php" class="btn btn-secondary">Bekor qilish</a>
                <button type="submit" class="btn btn-primary">
                    Mavzuni yaratish <i class="bi bi-plus-circle-fill"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Copy editor content to hidden input prior to submitting -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('createTopicForm');
    const hiddenInput = document.getElementById('hiddenContent');
    const editor = document.getElementById('editor');
    
    // Copy any previously submitted text if available (for error recovery)
    // Wait, since we are doing simple POST, we can inject PHP string if error occurred.
    <?php if (isset($_POST['content'])): ?>
    if (editor && typeof Quill !== 'undefined') {
        setTimeout(() => {
            const q = Quill.find(editor);
            if (q) {
                q.clipboard.dangerouslyPasteHTML(<?php echo json_encode($_POST['content']); ?>);
            }
        }, 100);
    }
    <?php endif; ?>

    if (form && hiddenInput && editor) {
        form.addEventListener('submit', (e) => {
            const q = Quill.find(editor);
            if (q) {
                const html = q.getSemanticHTML();
                hiddenInput.value = html;
                
                // Extra check for text content
                const text = q.getText().trim();
                if (text === '') {
                    e.preventDefault();
                    showToast("Mavzu mazmunini yozishingiz lozim.", "error");
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
