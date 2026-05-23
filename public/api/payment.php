<?php
/**
 * Tap Payments Integration API
 * POST /api/payment.php?action=create   → إنشاء معاملة دفع جديدة وإرجاع رابط الدفع
 * GET  /api/payment.php?action=callback → معالجة رد اتصال الدفع بعد إتمامه
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// ============ CONFIGURATION ============
// سعر الباقة المميزة وعملتها ومفاتيح Tap
$TAP_SECRET_KEY = 'YOUR_TAP_SECRET_KEY_HERE'; // ضَع مفتاح Tap السري الخاص بك هنا
$PRICE = 15.00;
$CURRENCY = 'USD'; // يمكنك تغييرها لـ SAR أو KWD أو EGP حسب الحاجة

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'create':
        if ($method !== 'POST') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }

        // التحقق من إعداد مفتاح Tap
        if ($TAP_SECRET_KEY === 'YOUR_TAP_SECRET_KEY_HERE' || empty($TAP_SECRET_KEY)) {
            jsonResponse([
                'error' => 'لم يتم إعداد مفتاح بوابة Tap بعد. يرجى إضافة مفتاح Tap السري في ملف payment.php على الخادم.',
                'code' => 'TAP_KEY_MISSING'
            ], 400);
        }

        // التحقق من تسجيل الدخول
        if (!isset($_SESSION['user_id'])) {
            jsonResponse(['error' => 'غير مصرح، يرجى تسجيل الدخول أولاً'], 401);
        }

        $userId = $_SESSION['user_id'];
        $userEmail = $_SESSION['email'];

        // إنشاء رابط العودة callback بشكل تلقائي بناء على اسم الدومين الحالي
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $callbackUrl = "$protocol://$host/public/api/payment.php?action=callback";

        // تجهيز بيانات الطلب لبوابة Tap
        $payload = [
            "amount" => $PRICE,
            "currency" => $CURRENCY,
            "threeDSecure" => true,
            "save_card" => false,
            "description" => "تفعيل الباقة المميزة مدى الحياة لاحتفالية كشف جنس المولود",
            "statement_descriptor" => "BABY_REVEAL",
            "customer" => [
                "first_name" => "Host",
                "email" => $userEmail
            ],
            "source" => ["id" => "src_all"],
            "redirect" => ["url" => $callbackUrl]
        ];

        // إرسال طلب cURL لإنشاء الفاتورة في Tap
        $ch = curl_init('https://api.tap.company/v2/charges');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $TAP_SECRET_KEY,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // التحقق من أخطاء cURL
        if ($response === false) {
            jsonResponse([
                'error' => 'فشل الاتصال بخوادم Tap',
                'details' => $curlError
            ], 500);
        }

        if ($httpCode !== 200) {
            jsonResponse([
                'error' => 'فشل إنشاء فاتورة الدفع من خوادم Tap',
                'http_code' => $httpCode,
                'details' => json_decode($response, true)
            ], 500);
        }

        $responseData = json_decode($response, true);
        $chargeId = $responseData['id'] ?? '';
        $transactionUrl = $responseData['transaction']['url'] ?? '';

        if (empty($chargeId) || empty($transactionUrl)) {
            jsonResponse(['error' => 'البيانات المسترجعة من Tap غير كاملة', 'raw' => $responseData], 500);
        }

        // حفظ معاملة الدفع في جدول المدفوعات بحالة INIT (انتظار الدفع)
        try {
            // إنشاء جدول payments تلقائياً لو مش موجود
            $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                charge_id VARCHAR(100) UNIQUE NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                currency VARCHAR(3) NOT NULL,
                status VARCHAR(20) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $stmt = $pdo->prepare("INSERT INTO payments (user_id, charge_id, amount, currency, status) VALUES (?, ?, ?, ?, 'INIT')");
            $stmt->execute([$userId, $chargeId, $PRICE, $CURRENCY]);

            // إرجاع رابط الدفع للواجهة الأمامية لإعادة التوجيه
            jsonResponse([
                'success' => true,
                'redirect_url' => $transactionUrl
            ]);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'فشل حفظ بيانات المعاملة في الخادم: ' . $e->getMessage()], 500);
        }
        break;

    case 'callback':
        $tapId = sanitize($_GET['tap_id'] ?? '');

        if (empty($tapId)) {
            header("Location: ../dashboard.html?payment=error&msg=missing_id");
            exit;
        }

        // إرسال طلب GET لـ Tap للتحقق من حالة العملية سرياً بالخلفية
        $ch = curl_init("https://api.tap.company/v2/charges/$tapId");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $TAP_SECRET_KEY,
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, HTTP_CODE);
        curl_close($ch);

        $paymentStatus = 'FAILED';
        $userId = null;

        if ($httpCode === 200) {
            $responseData = json_decode($response, true);
            $status = $responseData['status'] ?? '';

            if ($status === 'CAPTURED') {
                $paymentStatus = 'CAPTURED';
            }
        }

        // جلب العملية المحفوظة لتحديث حالتها
        $stmt = $pdo->prepare("SELECT user_id FROM payments WHERE charge_id = ?");
        $stmt->execute([$tapId]);
        $payment = $stmt->fetch();

        if ($payment) {
            $userId = $payment['user_id'];
            
            // تحديث حالة العملية في جدول الدفع
            $updatePayment = $pdo->prepare("UPDATE payments SET status = ? WHERE charge_id = ?");
            $updatePayment->execute([$paymentStatus, $tapId]);

            if ($paymentStatus === 'CAPTURED') {
                // ترقية حساب المستخدم ليكون نشطاً للأبد!
                $updateUser = $pdo->prepare("UPDATE users SET subscription_status = 'active' WHERE id = ?");
                $updateUser->execute([$userId]);

                // إعادة توجيه للوحة التحكم مع رسالة نجاح مبهجة
                header("Location: ../dashboard.html?payment=success");
                exit;
            }
        }

        // إذا فشلت العملية
        header("Location: ../dashboard.html?payment=failed");
        exit;
        break;

    default:
        jsonResponse(['error' => 'Action required or not supported'], 400);
}
