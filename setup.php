<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Sozlash | Setup Platform</title>
    <!-- Google Fonts & Bootstrap Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --accent-primary: #6366f1;
            --accent-secondary: #a855f7;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --glass-bg: rgba(30, 41, 59, 0.45);
            --glass-border: rgba(255, 255, 255, 0.08);
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent),
                        radial-gradient(circle at bottom left, rgba(168, 85, 247, 0.15), transparent),
                        var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff, var(--text-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }
        p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            line-height: 1.6;
        }
        .steps {
            text-align: left;
            margin: 30px 0;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--glass-border);
        }
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-family: 'Inter', sans-serif;
        }
        .step:last-child {
            margin-bottom: 0;
        }
        .step i {
            margin-right: 12px;
            font-size: 1.2rem;
        }
        .step.success i {
            color: #10b981;
        }
        .step.error i {
            color: #ef4444;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            color: white;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.05rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(99, 102, 241, 0.5);
        }
        .btn i {
            margin-left: 8px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Platformani Sozlash</h1>
    <p>Ma'lumotlar bazasi va dastlabki sozlamalarni o'rnatish tizimi.</p>
    
    <div class="steps">
        <?php
        $dbHost = '127.0.0.1';
        $dbUser = 'root';
        $dbPass = '';
        $dbName = 'online_forum';

        $steps = [];

        // Step 1: Connect to MySQL Server
        try {
            $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $steps[] = ['success', "MySQL serveriga muvaffaqiyatli ulanildi."];
        } catch (PDOException $e) {
            $steps[] = ['error', "MySQL serveriga ulanib bo'lmadi: " . $e->getMessage()];
        }

        if (empty($e)) {
            // Step 2: Create database
            try {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$dbName`");
                $steps[] = ['success', "'$dbName' ma'lumotlar bazasi yaratildi va tanlandi."];
            } catch (PDOException $ex) {
                $steps[] = ['error', "Bazani yaratib bo'lmadi: " . $ex->getMessage()];
            }

            // Step 3: Run table creations
            if (empty($ex)) {
                try {
                    // Create Tables
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `username` VARCHAR(50) NOT NULL UNIQUE,
                      `email` VARCHAR(100) NOT NULL UNIQUE,
                      `password_hash` VARCHAR(255) NOT NULL,
                      `role` ENUM('user', 'moderator', 'admin') NOT NULL DEFAULT 'user',
                      `avatar_color` VARCHAR(7) NOT NULL DEFAULT '#6366f1',
                      `bio` TEXT DEFAULT NULL,
                      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                    $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `name` VARCHAR(100) NOT NULL UNIQUE,
                      `description` TEXT NOT NULL,
                      `icon` VARCHAR(50) NOT NULL DEFAULT 'chat-left-text'
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                    $pdo->exec("CREATE TABLE IF NOT EXISTS `topics` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `category_id` INT NOT NULL,
                      `user_id` INT NOT NULL,
                      `title` VARCHAR(255) NOT NULL,
                      `content` MEDIUMTEXT NOT NULL,
                      `views` INT DEFAULT 0,
                      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
                      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                    $pdo->exec("CREATE TABLE IF NOT EXISTS `posts` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `topic_id` INT NOT NULL,
                      `user_id` INT NOT NULL,
                      `content` TEXT NOT NULL,
                      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      FOREIGN KEY (`topic_id`) REFERENCES `topics`(`id`) ON DELETE CASCADE,
                      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                    $pdo->exec("CREATE TABLE IF NOT EXISTS `likes` (
                      `id` INT AUTO_INCREMENT PRIMARY KEY,
                      `user_id` INT NOT NULL,
                      `topic_id` INT NOT NULL,
                      UNIQUE KEY `user_topic_like` (`user_id`, `topic_id`),
                      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                      FOREIGN KEY (`topic_id`) REFERENCES `topics`(`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                    $steps[] = ['success', "Barcha jadvallar muvaffaqiyatli yaratildi."];
                } catch (PDOException $ex2) {
                    $steps[] = ['error', "Jadvallarni yaratishda xatolik: " . $ex2->getMessage()];
                }

                // Step 4: Seed starter categories and default Admin
                if (empty($ex2)) {
                    try {
                        // Check if categories already exist
                        $stmt = $pdo->query("SELECT COUNT(*) FROM `categories`");
                        $count = $stmt->fetchColumn();
                        if ($count == 0) {
                            $starter_categories = [
                                ['Dasturlash', 'Veb, mobil va backend dasturlash bo\'yicha muhokamalar va tajribalar.', 'code-slash'],
                                ['Sun\'iy Intellekt', 'Neyrotarmoqlar, ChatGPT, Gemini va Sun\'iy Intellekt rivojlanishi.', 'cpu'],
                                ['Kiberxavfsizlik', 'Tizimlar xavfsizligi, etikal hakerlik va tarmoq mudofaasi.', 'shield-lock'],
                                ['Dizayn va UI/UX', 'Zamonaviy veb dizayn, Figma, ranglar nazariyasi va foydalanuvchi tajribasi.', 'palette'],
                                ['Erkin Mavzu', 'Istalgan boshqa mavzularda erkin va do\'stona fikr almashish burchagi.', 'chat-dots']
                            ];

                            $insert_cat = $pdo->prepare("INSERT INTO `categories` (`name`, `description`, `icon`) VALUES (?, ?, ?)");
                            foreach ($starter_categories as $cat) {
                                $insert_cat->execute($cat);
                            }
                            $steps[] = ['success', "Ilk forum kategoriyalari bazaga qo'shildi."];
                        } else {
                            $steps[] = ['success', "Kategoriyalar allaqachon mavjud."];
                        }

                        // Check if Admin exists
                        $stmt_u = $pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'");
                        $admin_count = $stmt_u->fetchColumn();
                        if ($admin_count == 0) {
                            $admin_pass = password_hash('adminpassword', PASSWORD_DEFAULT);
                            $insert_admin = $pdo->prepare("INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `avatar_color`, `bio`) VALUES (?, ?, ?, ?, ?, ?)");
                            $insert_admin->execute([
                                'admin',
                                'admin@forum.uz',
                                $admin_pass,
                                'admin',
                                '#ec4899',
                                'Forum asoschisi va bosh administrator.'
                            ]);
                            $steps[] = ['success', "Default Administrator yaratildi: <b>admin</b> / <b>adminpassword</b>"];
                        } else {
                            $steps[] = ['success', "Administrator allaqachon mavjud."];
                        }
                    } catch (PDOException $ex3) {
                        $steps[] = ['error', "Dastlabki ma'lumotlarni yozishda xatolik: " . $ex3->getMessage()];
                    }
                }
            }
        }

        // Render steps status
        $all_ok = true;
        foreach ($steps as $step) {
            $class = $step[0];
            $icon = $class === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
            if ($class === 'error') $all_ok = false;
            echo "<div class='step $class'><i class='bi $icon'></i><span>{$step[1]}</span></div>";
        }
        ?>
    </div>

    <?php if ($all_ok): ?>
        <p style="color: #10b981; font-weight: 500; margin-bottom: 25px;">
            <i class="bi bi-shield-check"></i> Tizim to'liq tayyor! Bosh sahifaga o'tib forumni sinab ko'rishingiz mumkin.
        </p>
        <a href="index.php" class="btn">Forumga o'tish <i class="bi bi-arrow-right"></i></a>
    <?php else: ?>
        <p style="color: #ef4444; font-weight: 500; margin-bottom: 25px;">
            <i class="bi bi-exclamation-circle"></i> Sozlash jarayonida xatolik yuz berdi. MySQL parametrlari va ruxsatlarini tekshiring.
        </p>
        <a href="setup.php" class="btn" style="background: linear-gradient(135deg, #ef4444, #f59e0b);">Qayta urinish <i class="bi bi-arrow-clockwise"></i></a>
    <?php endif; ?>
</div>

</body>
</html>
