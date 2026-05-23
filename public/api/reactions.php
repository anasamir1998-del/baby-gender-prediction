<?php
/**
 * Reactions API (Multi-Tenant)
 * GET  /api/reactions.php?slug=ilan&after=X  → جلب الريأكشنات الجديدة بعد معرف معين لحدث معين
 * POST /api/reactions.php?slug=ilan          → إرسال ريأكشن جديد لحدث معين
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$slug = sanitize($_GET['slug'] ?? '');

// التحقق من وجود الـ Slug وحلّه إلى event_id
$eventId = null;
if (!empty($slug)) {
    $stmt = $pdo->prepare("SELECT id FROM events WHERE slug = ?");
    $stmt->execute([$slug]);
    $event = $stmt->fetch();
    if ($event) {
        $eventId = intval($event['id']);
    } else {
        jsonResponse(['error' => 'الحدث المخصص غير موجود'], 404);
    }
}

if (!$eventId) {
    jsonResponse(['error' => 'الرابط المخصص مطلوب'], 400);
}

switch ($method) {
    case 'GET':
        $afterId = intval($_GET['after'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT id, emoji, 
            UNIX_TIMESTAMP(created_at) * 1000 as ts 
            FROM reactions 
            WHERE event_id = ? AND id > ? 
            ORDER BY created_at ASC 
            LIMIT 20");
        $stmt->execute([$eventId, $afterId]);
        
        $reactions = $stmt->fetchAll();
        jsonResponse($reactions);
        break;

    case 'POST':
        $data = getJsonInput();
        $emoji = $data['emoji'] ?? '';

        // التحقق من الإيموجي
        if (empty($emoji) || mb_strlen($emoji) > 10) {
            jsonResponse(['error' => 'Invalid emoji'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO reactions (event_id, emoji) VALUES (?, ?)");
        $stmt->execute([$eventId, $emoji]);

        jsonResponse([
            'success' => true,
            'id' => $pdo->lastInsertId()
        ], 201);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

// تنظيف الريأكشنات القديمة (أكثر من ساعة) - يتم كل 100 طلب تقريباً
if (rand(1, 100) === 1) {
    $pdo->exec("DELETE FROM reactions WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
}
