<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Faqat POST so\'rovlar qabul qilinadi.']);
    exit;
}

// Read raw JSON input or standard POST data
$input = json_decode(file_get_contents('php://input'), true);
$action = isset($input['action']) ? trim($input['action']) : (isset($_POST['action']) ? trim($_POST['action']) : '');

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Harakat (action) ko\'rsatilmagan.']);
    exit;
}

// User must be logged in for all API actions
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Ushbu amalni bajarish uchun tizimga kirishingiz lozim.']);
    exit;
}

$currentUser = current_user();

switch ($action) {
    case 'like':
        $topic_id = isset($input['topic_id']) ? (int)$input['topic_id'] : 0;
        
        if ($topic_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mavzu ID noto\'g\'ri.']);
            exit;
        }

        try {
            // Check if already liked
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `likes` WHERE `user_id` = ? AND `topic_id` = ?");
            $stmt->execute([$currentUser['id'], $topic_id]);
            $is_liked = $stmt->fetchColumn() > 0;

            if ($is_liked) {
                // Unlike
                $del = $pdo->prepare("DELETE FROM `likes` WHERE `user_id` = ? AND `topic_id` = ?");
                $del->execute([$currentUser['id'], $topic_id]);
                $liked = false;
            } else {
                // Like
                $ins = $pdo->prepare("INSERT INTO `likes` (`user_id`, `topic_id`) VALUES (?, ?)");
                $ins->execute([$currentUser['id'], $topic_id]);
                $liked = true;
            }

            // Get new likes count
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM `likes` WHERE `topic_id` = ?");
            $count_stmt->execute([$topic_id]);
            $likes_count = $count_stmt->fetchColumn();

            echo json_encode([
                'success' => true,
                'liked' => $liked,
                'likes_count' => (int)$likes_count
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Tizim xatosi: ' . $e->getMessage()]);
            exit;
        }
        break;

    case 'add_post':
        $topic_id = isset($input['topic_id']) ? (int)$input['topic_id'] : 0;
        $content = isset($input['content']) ? trim($input['content']) : '';

        // If Quill wysiwyg contains just whitespace tags, filter it
        $cleanContent = strip_tags($content, '<strong><em><p><ol><ul><li><a><br>');
        $cleanContent = trim($cleanContent);

        if ($topic_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mavzu ID xato.']);
            exit;
        }

        if (empty($cleanContent) || $cleanContent === '<p><br></p>') {
            echo json_encode(['success' => false, 'message' => 'Izoh bo\'sh bo\'lishi mumkin emas.']);
            exit;
        }

        try {
            // Check if topic exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `topics` WHERE `id` = ?");
            $stmt->execute([$topic_id]);
            if ($stmt->fetchColumn() == 0) {
                echo json_encode(['success' => false, 'message' => 'Mavzu topilmadi.']);
                exit;
            }

            // Insert new post
            $ins = $pdo->prepare("INSERT INTO `posts` (`topic_id`, `user_id`, `content`) VALUES (?, ?, ?)");
            $ins->execute([$topic_id, $currentUser['id'], $content]);
            $new_post_id = $pdo->lastInsertId();

            // Fetch newly created post details with user details
            $fetch = $pdo->prepare("
                SELECT p.id, p.content, p.created_at, u.username, u.avatar_color, u.role
                FROM `posts` p
                JOIN `users` u ON p.user_id = u.id
                WHERE p.id = ?
            ");
            $fetch->execute([$new_post_id]);
            $newPost = $fetch->fetch();

            echo json_encode([
                'success' => true,
                'post' => [
                    'id' => (int)$newPost['id'],
                    'content' => $newPost['content'],
                    'username' => esc($newPost['username']),
                    'avatar_color' => esc($newPost['avatar_color']),
                    'role' => esc($newPost['role']),
                    'created_at' => time_ago($newPost['created_at'])
                ]
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Xatolik yuz berdi: ' . $e->getMessage()]);
            exit;
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Noma\'lum harakat.']);
        exit;
}
