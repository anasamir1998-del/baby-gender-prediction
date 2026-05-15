<?php
/**
 * Presence API (تتبع المتواجدين)
 * GET  /api/presence.php                    → عدد المتواجدين حالياً
 * POST /api/presence.php                    → تحديث حالة التواجد (heartbeat)
 * DELETE /api/presence.php?session=X         → إزالة جلسة
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// اعتبر المستخدم متواجد إذا آخر heartbeat كان خلال 60 ثانية
$ACTIVE_WINDOW = 60;

switch ($method) {
    case 'GET':
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM presence 
            WHERE last_seen >= DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$ACTIVE_WINDOW]);
        $result = $stmt->fetch();
        
        jsonResponse(['count' => intval($result['count'])]);
        break;

    case 'POST':
        $data = getJsonInput();
        $sessionId = sanitize($data['sessionId'] ?? '');

        if (empty($sessionId)) {
            jsonResponse(['error' => 'Session ID required'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO presence (session_id, last_seen) 
            VALUES (?, NOW()) 
            ON DUPLICATE KEY UPDATE last_seen = NOW()");
        $stmt->execute([$sessionId]);

        jsonResponse(['success' => true]);
        break;

    case 'DELETE':
        $sessionId = sanitize($_GET['session'] ?? '');
        if (!empty($sessionId)) {
            $stmt = $pdo->prepare("DELETE FROM presence WHERE session_id = ?");
            $stmt->execute([$sessionId]);
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
