<?php
/**
 * Database Migration Script
 * يرقي قاعدة البيانات الحالية لتدعم نظام SaaS متعدد المستخدمين
 * ويحفظ البيانات القديمة ويربطها بحدث افتراضي للطفلة إيلان حتى لا يضيع أي شيء!
 */

require_once 'config.php';

try {
    $pdo->beginTransaction();

    echo "⏳ البدء في ترقية قاعدة البيانات...<br>";

    // 1. إنشاء جدول المستخدمين users
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        trial_ends_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        subscription_status ENUM('trial', 'active', 'expired') DEFAULT 'trial',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✔ تم إنشاء جدول users بنجاح.<br>";

    // 2. إنشاء جدول الاحتفاليات events
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
    echo "✔ تم إنشاء جدول events بنجاح.<br>";

    // 3. إنشاء جدول المعاملات المالية payments
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        charge_id VARCHAR(100) UNIQUE NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) NOT NULL,
        status VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✔ تم إنشاء جدول payments بنجاح.<br>";

    // 4. ترقية الجداول القديمة بإضافة عمود event_id إذا لم يكن موجوداً
    $tablesToUpgrade = ['predictions', 'live_chat', 'reactions', 'presence'];
    foreach ($tablesToUpgrade as $table) {
        // التحقق من وجود العمود مسبقاً
        $check = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'event_id'");
        if ($check->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN event_id INT DEFAULT NULL");
            $pdo->exec("ALTER TABLE `$table` ADD INDEX `idx_event_id` (event_id)");
            echo "✔ تم إضافة عمود event_id لجدول `$table` بنجاح.<br>";
        } else {
            echo "ℹ عمود event_id موجود بالفعل في جدول `$table`.<br>";
        }
    }

    // 5. الحفاظ على البيانات القديمة وربطها بمستند افتراضي (إيلان)
    // نتحقق أولاً هل يوجد مستخدم افتراضي
    $adminEmail = 'ilan@baby.com';
    $checkUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkUser->execute([$adminEmail]);
    $userId = null;

    if ($checkUser->rowCount() === 0) {
        // إنشاء مستخدم افتراضي
        $dummyPassword = password_hash('ilan2026', PASSWORD_BCRYPT);
        // فترة تجربة تنتهي بعد 100 سنة أو حساب نشط فوراً
        $trialEnds = date('Y-m-d H:i:s', strtotime('+100 years'));
        
        $insertUser = $pdo->prepare("INSERT INTO users (email, password_hash, trial_ends_at, subscription_status) VALUES (?, ?, ?, 'active')");
        $insertUser->execute([$adminEmail, $dummyPassword, $trialEnds]);
        $userId = $pdo->lastInsertId();
        echo "✔ تم إنشاء الحساب الافتراضي للأدمن بنجاح ($adminEmail).<br>";
    } else {
        $userId = $checkUser->fetch()['id'];
    }

    // نتحقق هل توجد احتفالية افتراضية 'ilan'
    $checkEvent = $pdo->prepare("SELECT id FROM events WHERE slug = 'ilan'");
    $checkEvent->execute();
    $eventId = null;

    if ($checkEvent->rowCount() === 0) {
        // جلب الإعداد الحالي للتوقيت المستهدف
        $targetDate = null;
        $getSetting = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'targetDate'");
        if ($getSetting->rowCount() > 0) {
            $targetDate = $getSetting->fetch()['setting_value'];
        }
        if (!$targetDate) {
            $targetDate = date('Y-m-d H:i:s', strtotime('+1 hour'));
        }

        $insertEvent = $pdo->prepare("INSERT INTO events (user_id, slug, baby_name, sub_baby_name, revealed_gender, target_date, admin_pin) VALUES (?, 'ilan', 'إيلان', 'نايف باوزير', 'girl', ?, '2030')");
        $insertEvent->execute([$userId, $targetDate]);
        $eventId = $pdo->lastInsertId();
        echo "✔ تم إنشاء الحدث الافتراضي (ilan) بنجاح.<br>";
    } else {
        $eventId = $checkEvent->fetch()['id'];
    }

    // ربط كافة البيانات القديمة التي تحتوي على NULL في event_id بالحدث الافتراضي إيلان
    foreach ($tablesToUpgrade as $table) {
        $updateCount = $pdo->exec("UPDATE `$table` SET event_id = $eventId WHERE event_id IS NULL");
        if ($updateCount > 0) {
            echo "✔ تم ربط $updateCount سجل قديم في جدول `$table` بالحدث الافتراضي (إيلان).<br>";
        }
    }

    $pdo->commit();
    echo "🎉 <b>تمت عملية الترقية وترحيل البيانات بنجاح تام!</b>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ <b>فشلت الترقية:</b> " . $e->getMessage();
}
