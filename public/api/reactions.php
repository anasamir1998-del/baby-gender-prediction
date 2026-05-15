<?php
/**
 * Reactions API
 * GET  /api/reactions.php?after=X  → جلب الريأكشنات الجديدة بعد معرف معين
 * POST /api/reactions.php          → إرسال ريأكشن جديد
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $afterId = intval($_GET['after'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT id, emoji, 
            UNIX_TIMESTAMP(created_at) * 1000 as ts 
            FROM reactions 
            WHERE id > ? 
            ORDER BY created_at ASC 
            LIMIT 20");
        $stmt->execute([$afterId]);
        
        $reactions = $stmt->fetchAll();
        jsonResponse($reactions);
        break;

    case 'POST':
        $data = getJsonInput();
        $emoji = $data['emoji'] ?? '';

        // Validate emoji (basic check)
        if (empty($emoji) || mb_strlen($emoji) > 10) {
            jsonResponse(['error' => 'Invalid emoji'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO reactions (emoji) VALUES (?)");
        $stmt->execute([$emoji]);

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
