<?php
/**
 * Events API
 * GET    /api/events.php?slug=ilan  → جلب بيانات احتفالية عامة بالـ slug (متاح للزوار)
 * GET    /api/events.php            → جلب بيانات احتفالية المستخدم الحالي (يحتاج تسجيل دخول)
 * POST   /api/events.php            → إنشاء أو تعديل بيانات الاحتفالية (يحتاج تسجيل دخول)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

// ============ PUBLIC VISITOR FLOW ============
if ($method === 'GET' && isset($_GET['slug'])) {
    $slug = sanitize($_GET['slug']);

    if (empty($slug)) {
        jsonResponse(['error' => 'الرابط المخصص مطلوب'], 400);
    }

    // جلب الاحتفالية ومعلومات حساب المالك للتحقق من صلاحية الاشتراك
    $stmt = $pdo->prepare("SELECT e.*, u.subscription_status, u.trial_ends_at 
        FROM events e 
        JOIN users u ON e.user_id = u.id 
        WHERE e.slug = ?");
    $stmt->execute([$slug]);
    $event = $stmt->fetch();

    if (!$event) {
        jsonResponse(['error' => 'الاحتفالية غير موجودة'], 404);
    }

    // التحقق من انتهاء الفترة التجريبية/الاشتراك وصلاحية الحساب
    $now = time();
    $trialEndTs = strtotime($event['trial_ends_at']);
    
    // يعتبر منتهي الصلاحية إذا كانت الحالة expired، أو كان (trial أو active) وانتهى الوقت المحدد له
    $isExpired = $event['subscription_status'] === 'expired' || 
                 (($event['subscription_status'] === 'trial' || $event['subscription_status'] === 'active') && $trialEndTs <= $now);

    if ($isExpired) {
        // تحديث الحالة لـ expired في الخلفية
        if ($event['subscription_status'] !== 'expired') {
            $update = $pdo->prepare("UPDATE users SET subscription_status = 'expired' WHERE id = ?");
            $update->execute([$event['user_id']]);
        }
        
        jsonResponse([
            'success' => false,
            'status' => 'expired',
            'message' => 'عذراً، انتهت صلاحية اشتراك هذه الاحتفالية.'
        ]);
    }

    // إرجاع البيانات العامة
    jsonResponse([
        'success' => true,
        'id' => $event['id'],
        'baby_name' => $event['baby_name'],
        'sub_baby_name' => $event['sub_baby_name'],
        'revealed_gender' => $event['revealed_gender'],
        'target_date' => $event['target_date'],
        'admin_pin' => $event['admin_pin'], // مستخدم لتخطي لوحة النتائج محلياً
        'status' => $event['subscription_status'],
        'suspense_messages' => $event['suspense_messages'],
        'custom_card_image' => $event['custom_card_image']
    ]);
}

// ============ AUTHENTICATED USER FLOW ============
// التحقق من تسجيل الدخول للعمليات الأخرى
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'غير مصرح بالوصول، يرجى تسجيل الدخول'], 401);
}

$userId = $_SESSION['user_id'];

switch ($method) {
    case 'GET':
        // جلب بيانات احتفالية المستخدم الحالي
        $stmt = $pdo->prepare("SELECT * FROM events WHERE user_id = ?");
        $stmt->execute([$userId]);
        $event = $stmt->fetch();
        
        if ($event) {
            jsonResponse(['success' => true, 'event' => $event]);
        } else {
            jsonResponse(['success' => true, 'event' => null, 'message' => 'لا توجد احتفالية نشطة لهذا الحساب بعد']);
        }
        break;

    case 'POST':
        $data = getJsonInput();

        $babyName = sanitize($data['baby_name'] ?? 'لم يُحدد');
        $subBabyName = sanitize($data['sub_baby_name'] ?? '');
        $revealedGender = ($data['revealed_gender'] ?? 'girl') === 'boy' ? 'boy' : 'girl';
        $targetDate = sanitize($data['target_date'] ?? '');
        $slug = strtolower(trim(sanitize($data['slug'] ?? '')));
        $adminPin = sanitize($data['admin_pin'] ?? '2030');
        $customCardImage = $data['custom_card_image'] ?? '';

        $suspenseMessagesRaw = $data['suspense_messages'] ?? null;
        $suspenseMessages = null;
        if (is_array($suspenseMessagesRaw)) {
            $cleanedMessages = [];
            foreach ($suspenseMessagesRaw as $msg) {
                if (isset($msg['text']) && isset($msg['delay'])) {
                    // Sanitize text but keep emojis (strip_tags only is fine)
                    $text = htmlspecialchars(strip_tags(trim($msg['text'])), ENT_QUOTES, 'UTF-8');
                    $delay = floatval($msg['delay']);
                    if (!empty($text) && $delay > 0) {
                        $cleanedMessages[] = [
                            'text' => $text,
                            'delay' => $delay
                        ];
                    }
                }
            }
            $suspenseMessages = json_encode($cleanedMessages, JSON_UNESCAPED_UNICODE);
        }

        if (empty($slug)) {
            jsonResponse(['error' => 'الرابط المخصص (Slug) مطلوب'], 400);
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
            jsonResponse(['error' => 'يجب أن يحتوي الرابط المخصص على أحرف إنجليزية وأرقام وعلامات - أو _ فقط'], 400);
        }

        // التحقق من عدم تكرار الـ Slug مع احتفالية مستخدم آخر
        $stmt = $pdo->prepare("SELECT id FROM events WHERE slug = ? AND user_id != ?");
        $stmt->execute([$slug, $userId]);
        if ($stmt->rowCount() > 0) {
            jsonResponse(['error' => 'هذا الرابط المخصص محجوز بالفعل لاحتفالية أخرى، اختر اسماً آخر'], 400);
        }

        if (empty($targetDate)) {
            jsonResponse(['error' => 'تاريخ الكشف والعد التنازلي مطلوب'], 400);
        }

        // التحقق مما إذا كان المستخدم يملك حدثاً بالفعل لتعديله أو إنشاء حدث جديد
        $stmt = $pdo->prepare("SELECT id FROM events WHERE user_id = ?");
        $stmt->execute([$userId]);
        $existingEvent = $stmt->fetch();

        try {
            if ($existingEvent) {
                // تعديل
                $update = $pdo->prepare("UPDATE events 
                    SET slug = ?, baby_name = ?, sub_baby_name = ?, revealed_gender = ?, target_date = ?, admin_pin = ?, suspense_messages = ?, custom_card_image = ? 
                    WHERE user_id = ?");
                $update->execute([$slug, $babyName, $subBabyName, $revealedGender, $targetDate, $adminPin, $suspenseMessages, $customCardImage, $userId]);
                
                jsonResponse([
                    'success' => true,
                    'message' => 'تم تحديث بيانات الاحتفالية بنجاح!',
                    'event_id' => $existingEvent['id']
                ]);
            } else {
                // إنشاء جديد
                $insert = $pdo->prepare("INSERT INTO events (user_id, slug, baby_name, sub_baby_name, revealed_gender, target_date, admin_pin, suspense_messages, custom_card_image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insert->execute([$userId, $slug, $babyName, $subBabyName, $revealedGender, $targetDate, $adminPin, $suspenseMessages, $customCardImage]);
                
                jsonResponse([
                    'success' => true,
                    'message' => 'تم إنشاء الاحتفالية بنجاح! 🎉',
                    'event_id' => $pdo->lastInsertId()
                ]);
            }
        } catch (PDOException $e) {
            jsonResponse(['error' => 'فشلت معالجة الطلب: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
