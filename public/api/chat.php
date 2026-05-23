<?php
/**
 * Live Chat API (Multi-Tenant)
 * GET  /api/chat.php?slug=ilan            → جلب آخر 50 رسالة لحدث معين
 * GET  /api/chat.php?slug=ilan&after=X    → جلب الرسائل الجديدة بعد معرف معين لحدث معين (للـ polling)
 * POST /api/chat.php?slug=ilan            → إرسال رسالة جديدة لحدث معين
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
        if (isset($_GET['after'])) {
            // جلب الرسائل الجديدة فقط (بعد معرف معين) لحدث معين
            $afterId = intval($_GET['after']);
            $stmt = $pdo->prepare("SELECT id, name, text, sender_id, 
                UNIX_TIMESTAMP(created_at) * 1000 as ts 
                FROM live_chat 
                WHERE event_id = ? AND id > ? 
                ORDER BY created_at ASC 
                LIMIT 50");
            $stmt->execute([$eventId, $afterId]);
        } else {
            // جلب آخر 50 رسالة لحدث معين
            $stmt = $pdo->prepare("SELECT id, name, text, sender_id, 
                UNIX_TIMESTAMP(created_at) * 1000 as ts 
                FROM live_chat 
                WHERE event_id = ? 
                ORDER BY created_at DESC 
                LIMIT 50");
            $stmt->execute([$eventId]);
        }
        
        $messages = $stmt->fetchAll();
        
        // إذا كنا نجلب آخر 50، نعكس الترتيب لتبدأ من الأقدم إلى الأحدث
        if (!isset($_GET['after'])) {
            $messages = array_reverse($messages);
        }
        
        jsonResponse($messages);
        break;

    case 'POST':
        $data = getJsonInput();
        
        $name = sanitize($data['name'] ?? '');
        $text = sanitize($data['text'] ?? '');
        $senderId = sanitize($data['senderId'] ?? '');

        if (empty($text)) {
            jsonResponse(['error' => 'النص مطلوب'], 400);
        }
        if (empty($name)) $name = 'زائر';
        if (empty($senderId)) $senderId = 'unknown';

        $stmt = $pdo->prepare("INSERT INTO live_chat (event_id, name, text, sender_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$eventId, $name, $text, $senderId]);

        jsonResponse([
            'success' => true,
            'id' => $pdo->lastInsertId()
        ], 201);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
