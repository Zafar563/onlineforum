<?php
/**
 * Database and System Configurations for Online Forum Platform
 */

// 1. Session Setup (Secure settings)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    // On localhost, cookie_secure might cause issues, so we leave it off unless HTTPS is detected
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// 2. DB Configuration Constants
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'online_forum');
define('DB_USER', 'root');
define('DB_PASS', '');

// 3. Database Connection establishment using PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // If the database online_forum doesn't exist yet, we allow connection to server to run setup.php
    $pdo = null;
}

// 4. XSS Protection Helper
if (!function_exists('esc')) {
    function esc($text) {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// 5. CSRF Token Protection Helpers
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

// 6. Authentication & Authorization Helpers
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('current_user')) {
    function current_user() {
        if (!is_logged_in()) return null;
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'avatar_color' => $_SESSION['avatar_color'] ?? '#6366f1'
        ];
    }
}

if (!function_exists('has_role')) {
    function has_role($roles) {
        if (!is_logged_in()) return false;
        if (is_array($roles)) {
            return in_array($_SESSION['role'], $roles);
        }
        return $_SESSION['role'] === $roles;
    }
}

// 7. Time ago Formatter Helper
if (!function_exists('time_ago')) {
    function time_ago($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 1) {
            return 'Hozirgincha';
        }
        
        $tokens = [
            31536000 => 'yil oldin',
            2592000 => 'oy oldin',
            604800 => 'hafta oldin',
            86400 => 'kun oldin',
            3600 => 'soat oldin',
            60 => 'daqiqa oldin',
            1 => 'soniya oldin'
        ];
        
        foreach ($tokens as $unit => $text) {
            if ($diff < $unit) continue;
            $numberOfUnits = floor($diff / $unit);
            return $numberOfUnits . ' ' . $text;
        }
        return $datetime;
    }
}
