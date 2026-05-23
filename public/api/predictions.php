<?php
/**
 * Predictions API (Multi-Tenant)
 * GET    /api/predictions.php?slug=ilan          → جلب كل توقعات حدث معين
 * GET    /api/predictions.php?slug=ilan&count=1  → جلب إحصائيات حدث معين
 * POST   /api/predictions.php?slug=ilan          → إضافة توقع لحدث معين
 * DELETE /api/predictions.php?id=X               → حذف توقع واحد (يتطلب تحقق)
 * DELETE /api/predictions.php?slug=ilan&all=1    → حذف كل التوقعات لحدث معين (يتطلب تحقق)
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
    $stmt = $pdo->prepare("SELECT id, user_id FROM events WHERE slug = ?");
    $stmt->execute([$slug]);
    $event = $stmt->fetch();
    if ($event) {
        $eventId = intval($event['id']);
        $eventOwnerId = intval($event['user_id']);
    } else {
        jsonResponse(['error' => 'الحدث المخصص غير موجود'], 404);
    }
}

switch ($method) {
    case 'GET':
        if (!$eventId) {
            jsonResponse(['error' => 'الرابط المخصص مطلوب'], 400);
        }

        if (isset($_GET['count'])) {
            // إحصائيات التوقعات لحدث معين
            $stmt = $pdo->prepare("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN gender = 'boy' THEN 1 ELSE 0 END) as boys,
                SUM(CASE WHEN gender = 'girl' THEN 1 ELSE 0 END) as girls
                FROM predictions 
                WHERE event_id = ?");
            $stmt->execute([$eventId]);
            $result = $stmt->fetch();
            jsonResponse($result);
        } else {
            // كل توقعات حدث معين
            $stmt = $pdo->prepare("SELECT id, name, relation, gender, date_text, 
                UNIX_TIMESTAMP(created_at) * 1000 as timestamp 
                FROM predictions 
                WHERE event_id = ? 
                ORDER BY created_at ASC");
            $stmt->execute([$eventId]);
            $predictions = $stmt->fetchAll();
            jsonResponse($predictions);
        }
        break;

    case 'POST':
        if (!$eventId) {
            jsonResponse(['error' => 'الرابط المخصص مطلوب'], 400);
        }

        $data = getJsonInput();
        $name = sanitize($data['name'] ?? '');
        $relation = sanitize($data['relation'] ?? '');
        $gender = ($data['gender'] ?? '') === 'boy' ? 'boy' : 'girl';
        $dateText = sanitize($data['date'] ?? date('Y/m/d h:i A'));

        if (empty($name)) {
            jsonResponse(['error' => 'الاسم مطلوب'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO predictions (event_id, name, relation, gender, date_text) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$eventId, $name, $relation, $gender, $dateText]);

        jsonResponse([
            'success' => true,
            'id' => $pdo->lastInsertId(),
            'message' => 'تم حفظ التوقع بنجاح'
        ], 201);
        break;

    case 'DELETE':
        // الحذف يتطلب تسجيل دخول كمالك الحدث
        if (!isset($_SESSION['user_id'])) {
            jsonResponse(['error' => 'غير مصرح بالوصول، يرجى تسجيل الدخول'], 401);
        }
        $currentUserId = $_SESSION['user_id'];

        if (isset($_GET['all']) && $_GET['all'] == '1') {
            if (!$eventId) {
                jsonResponse(['error' => 'الرابط المخصص مطلوب'], 400);
            }
            // التأكد من أن المستخدم الحالي هو مالك الحدث
            if ($currentUserId !== $eventOwnerId) {
                jsonResponse(['error' => 'غير مصرح لك بحذف محتويات هذا الحدث'], 403);
            }

            // حذف كل توقعات هذا الحدث
            $stmt = $pdo->prepare("DELETE FROM predictions WHERE event_id = ?");
            $stmt->execute([$eventId]);
            jsonResponse(['success' => true, 'message' => 'تم حذف جميع توقعات هذا الحدث بنجاح']);
            
        } elseif (isset($_GET['id'])) {
            // حذف توقع واحد
            $id = intval($_GET['id']);
            
            // جلب التوقع للتحقق من ملكية الحدث المترابط
            $stmt = $pdo->prepare("SELECT p.event_id, e.user_id 
                FROM predictions p 
                JOIN events e ON p.event_id = e.id 
                WHERE p.id = ?");
            $stmt->execute([$id]);
            $pred = $stmt->fetch();

            if (!$pred) {
                jsonResponse(['error' => 'التوقع غير موجود'], 404);
            }

            if (intval($pred['user_id']) !== $currentUserId) {
                jsonResponse(['error' => 'غير مصرح لك بحذف هذا التوقع'], 403);
            }

            $stmt = $pdo->prepare("DELETE FROM predictions WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['success' => true, 'message' => 'تم حذف التوقع بنجاح']);
        } else {
            jsonResponse(['error' => 'يجب تحديد المعرف أو all=1'], 400);
        }
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
