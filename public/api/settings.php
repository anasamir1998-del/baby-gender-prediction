<?php
/**
 * Settings API
 * GET  /api/settings.php?key=targetDate  → جلب إعداد
 * POST /api/settings.php                 → حفظ إعداد
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $key = sanitize($_GET['key'] ?? 'targetDate');
        
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        if ($result) {
            jsonResponse(['key' => $key, 'value' => $result['setting_value']]);
        } else {
            jsonResponse(['key' => $key, 'value' => null]);
        }
        break;

    case 'POST':
        $data = getJsonInput();
        $key = sanitize($data['key'] ?? 'targetDate');
        $value = $data['value'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$key, $value]);

        jsonResponse(['success' => true, 'message' => 'تم حفظ الإعداد']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
