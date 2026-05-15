<?php
/**
 * Live Chat API
 * GET  /api/chat.php              → جلب آخر 50 رسالة
 * GET  /api/chat.php?after=X      → جلب الرسائل بعد معرف معين (للـ polling)
 * POST /api/chat.php              → إرسال رسالة جديدة
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['after'])) {
            // جلب الرسائل الجديدة فقط (بعد معرف معين)
            $afterId = intval($_GET['after']);
            $stmt = $pdo->prepare("SELECT id, name, text, sender_id, 
                UNIX_TIMESTAMP(created_at) * 1000 as ts 
                FROM live_chat 
                WHERE id > ? 
                ORDER BY created_at ASC 
                LIMIT 50");
            $stmt->execute([$afterId]);
        } else {
            // جلب آخر 50 رسالة
            $stmt = $pdo->query("SELECT id, name, text, sender_id, 
                UNIX_TIMESTAMP(created_at) * 1000 as ts 
                FROM live_chat 
                ORDER BY created_at DESC 
                LIMIT 50");
        }
        
        $messages = $stmt->fetchAll();
        
        // إذا كنا نجلب آخر 50، نعكس الترتيب عشان تكون من القديم للجديد
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

        $stmt = $pdo->prepare("INSERT INTO live_chat (name, text, sender_id) VALUES (?, ?, ?)");
        $stmt->execute([$name, $text, $senderId]);

        jsonResponse([
            'success' => true,
            'id' => $pdo->lastInsertId()
        ], 201);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
