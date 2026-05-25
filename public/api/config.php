<?php
// ============ DATABASE CONFIGURATION ============
// غيّر البيانات دي حسب إعدادات هوستنجر

$DB_HOST = 'localhost';          // عادةً localhost في هوستنجر
$DB_NAME = 'u127645123_baby';    // اسم قاعدة البيانات
$DB_USER = 'u127645123_admin';   // اسم المستخدم
$DB_PASS = 'Ss@371998';          // كلمة السر


// ============ CORS & HEADERS ============
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============ DATABASE CONNECTION ============
try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
    exit;
}

// ============ HELPER FUNCTIONS ============
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function sanitize($str) {
    return htmlspecialchars(strip_tags(trim($str ?? '')), ENT_QUOTES, 'UTF-8');
}

// ============ AUTO-MIGRATION: إنشاء الجداول تلقائياً ============
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        trial_ends_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        subscription_status ENUM('trial', 'active', 'expired') DEFAULT 'trial',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        slug VARCHAR(50) UNIQUE NOT NULL,
        baby_name VARCHAR(100) DEFAULT 'لم يُحدد',
        sub_baby_name VARCHAR(100) DEFAULT '',
        revealed_gender ENUM('boy', 'girl') DEFAULT 'girl',
        target_date TIMESTAMP NULL,
        admin_pin VARCHAR(20) DEFAULT '2030',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS predictions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT DEFAULT NULL,
        name VARCHAR(100) NOT NULL,
        relation VARCHAR(100) DEFAULT '',
        gender ENUM('boy','girl') NOT NULL,
        date_text VARCHAR(100) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_gender (gender),
        INDEX idx_created (created_at),
        INDEX idx_event_id (event_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS live_chat (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT DEFAULT NULL,
        name VARCHAR(100) NOT NULL,
        text VARCHAR(500) NOT NULL,
        sender_id VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at),
        INDEX idx_event_id (event_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT DEFAULT NULL,
        emoji VARCHAR(10) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at),
        INDEX idx_event_id (event_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS presence (
        session_id VARCHAR(50) PRIMARY KEY,
        event_id INT DEFAULT NULL,
        last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_last_seen (last_seen),
        INDEX idx_event_id (event_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        charge_id VARCHAR(100) UNIQUE NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) NOT NULL,
        status VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    // تجاهل - الجداول موجودة بالفعل
}
