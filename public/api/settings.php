<?php
/**
 * Settings API
 * GET  /api/settings.php?key=targetDate  → جلب إعداد
 * POST /api/settings.php                 → حفظ إعداد
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $key = sanitize($_GET['key'] ?? 'targetDate');
            $slug = sanitize($_GET['slug'] ?? '');
            
            // 1. If key is targetDate and slug is provided, fetch from events table
            if ($key === 'targetDate' && !empty($slug)) {
                $stmtEvent = $pdo->prepare("SELECT target_date FROM events WHERE slug = ?");
                $stmtEvent->execute([$slug]);
                $event = $stmtEvent->fetch();
                if ($event && !empty($event['target_date'])) {
                    jsonResponse(['key' => $key, 'value' => $event['target_date']]);
                }
            }

            // 2. Fallback to settings table
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
            $slug = sanitize($data['slug'] ?? '');

            // 1. Save in settings table
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$key, $value]);

            // 2. If key is targetDate and slug is provided, also update events table!
            if ($key === 'targetDate' && !empty($slug)) {
                $mysqlDate = null;
                if (!empty($value)) {
                    // If it is already in MySQL datetime format (YYYY-MM-DD HH:MM:SS), save it directly
                    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
                        $mysqlDate = $value;
                    } else {
                        // Otherwise, parse it with strtotime (fallback for ISO dates)
                        $timestamp = strtotime($value);
                        if ($timestamp !== false) {
                            $mysqlDate = date('Y-m-d H:i:s', $timestamp);
                        }
                    }
                }
                
                $stmtEvent = $pdo->prepare("UPDATE events SET target_date = ? WHERE slug = ?");
                $stmtEvent->execute([$mysqlDate, $slug]);
            }

            jsonResponse(['success' => true, 'message' => 'تم حفظ الإعداد']);
            break;

        default:
            jsonResponse(['error' => 'Method not allowed'], 405);
    }
} catch (PDOException $e) {
    jsonResponse([
        'error' => 'Database error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 500);
} catch (Exception $e) {
    jsonResponse([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ], 500);
}
