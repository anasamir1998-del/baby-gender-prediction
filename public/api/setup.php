<?php
/**
 * Database Setup Script
 * شغّل الملف ده مرة واحدة بس لإنشاء الجداول
 * بعدها احذفه أو غيّر اسمه عشان الأمان
 * 
 * استخدام: افتح في المتصفح https://yourdomain.com/api/setup.php
 */

require_once 'config.php';

try {
    // جدول التوقعات
    $pdo->exec("CREATE TABLE IF NOT EXISTS predictions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        relation VARCHAR(100) DEFAULT '',
        gender ENUM('boy','girl') NOT NULL,
        date_text VARCHAR(100) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_gender (gender),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // جدول الإعدادات
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // جدول الدردشة
    $pdo->exec("CREATE TABLE IF NOT EXISTS live_chat (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        text VARCHAR(500) NOT NULL,
        sender_id VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // جدول الريأكشنات
    $pdo->exec("CREATE TABLE IF NOT EXISTS reactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        emoji VARCHAR(10) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // جدول المتواجدين
    $pdo->exec("CREATE TABLE IF NOT EXISTS presence (
        session_id VARCHAR(50) PRIMARY KEY,
        last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_last_seen (last_seen)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // إدراج إعداد افتراضي للموعد (ساعة من الآن)
    $defaultDate = date('c', strtotime('+1 hour'));
    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('targetDate', ?)");
    $stmt->execute([$defaultDate]);

    jsonResponse([
        'success' => true,
        'message' => '✅ تم إنشاء جميع الجداول بنجاح! يمكنك حذف هذا الملف الآن.',
        'tables' => ['predictions', 'settings', 'live_chat', 'reactions', 'presence']
    ]);

} catch (PDOException $e) {
    jsonResponse(['error' => 'Setup failed', 'message' => $e->getMessage()], 500);
}
