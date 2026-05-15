<?php
/**
 * Predictions API
 * GET    /api/predictions.php          → جلب كل التوقعات
 * GET    /api/predictions.php?count=1  → جلب عدد التوقعات فقط
 * POST   /api/predictions.php          → إضافة توقع جديد
 * DELETE /api/predictions.php?id=X     → حذف توقع واحد
 * DELETE /api/predictions.php?all=1    → حذف كل التوقعات
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['count'])) {
            // عدد التوقعات فقط (للـ live badge)
            $stmt = $pdo->query("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN gender = 'boy' THEN 1 ELSE 0 END) as boys,
                SUM(CASE WHEN gender = 'girl' THEN 1 ELSE 0 END) as girls
                FROM predictions");
            $result = $stmt->fetch();
            jsonResponse($result);
        } else {
            // كل التوقعات
            $stmt = $pdo->query("SELECT id, name, relation, gender, date_text, 
                UNIX_TIMESTAMP(created_at) * 1000 as timestamp 
                FROM predictions ORDER BY created_at ASC");
            $predictions = $stmt->fetchAll();
            jsonResponse($predictions);
        }
        break;

    case 'POST':
        $data = getJsonInput();
        
        $name = sanitize($data['name'] ?? '');
        $relation = sanitize($data['relation'] ?? '');
        $gender = ($data['gender'] ?? '') === 'boy' ? 'boy' : 'girl';
        $dateText = sanitize($data['date'] ?? date('Y/m/d h:i A'));

        if (empty($name)) {
            jsonResponse(['error' => 'الاسم مطلوب'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO predictions (name, relation, gender, date_text) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $relation, $gender, $dateText]);

        jsonResponse([
            'success' => true,
            'id' => $pdo->lastInsertId(),
            'message' => 'تم حفظ التوقع بنجاح'
        ], 201);
        break;

    case 'DELETE':
        if (isset($_GET['all']) && $_GET['all'] == '1') {
            // حذف الكل
            $pdo->exec("DELETE FROM predictions");
            jsonResponse(['success' => true, 'message' => 'تم حذف جميع التوقعات']);
        } elseif (isset($_GET['id'])) {
            // حذف توقع واحد
            $id = intval($_GET['id']);
            $stmt = $pdo->prepare("DELETE FROM predictions WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                jsonResponse(['success' => true, 'message' => 'تم الحذف']);
            } else {
                jsonResponse(['error' => 'التوقع غير موجود'], 404);
            }
        } else {
            jsonResponse(['error' => 'يجب تحديد المعرف أو all=1'], 400);
        }
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
