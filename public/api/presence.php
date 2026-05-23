<?php
/**
 * Presence API (تتبع المتواجدين - Multi-Tenant)
 * GET  /api/presence.php?slug=ilan                    → عدد المتواجدين حالياً في حدث معين
 * POST /api/presence.php?slug=ilan                    → تحديث حالة التواجد (heartbeat) لحدث معين
 * DELETE /api/presence.php?slug=ilan&session=X         → إزالة جلسة لحدث معين
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

// اعتبر المستخدم متواجد إذا آخر heartbeat كان خلال 60 ثانية
$ACTIVE_WINDOW = 60;

switch ($method) {
    case 'GET':
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM presence 
            WHERE event_id = ? AND last_seen >= DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$eventId, $ACTIVE_WINDOW]);
        $result = $stmt->fetch();
        
        jsonResponse(['count' => intval($result['count'])]);
        break;

    case 'POST':
        $data = getJsonInput();
        $sessionId = sanitize($data['sessionId'] ?? '');

        if (empty($sessionId)) {
            jsonResponse(['error' => 'Session ID required'], 400);
        }

        // تضمين event_id في الجلسة
        $stmt = $pdo->prepare("INSERT INTO presence (session_id, event_id, last_seen) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE event_id = ?, last_seen = NOW()");
        $stmt->execute([$sessionId, $eventId, $eventId]);

        jsonResponse(['success' => true]);
        break;

    case 'DELETE':
        $sessionId = sanitize($_GET['session'] ?? '');
        if (!empty($sessionId)) {
            $stmt = $pdo->prepare("DELETE FROM presence WHERE session_id = ? AND event_id = ?");
            $stmt->execute([$sessionId, $eventId]);
        }
        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}

// تنظيف الجلسات المنتهية (أكثر من 5 دقائق) - يتم عشوائياً
if (rand(1, 20) === 1) {
    $pdo->exec("DELETE FROM presence WHERE last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
}
