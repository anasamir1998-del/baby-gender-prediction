<?php
/**
 * Authentication API
 * POST   /api/auth.php?action=register  → تسجيل حساب جديد
 * POST   /api/auth.php?action=login     → تسجيل الدخول
 * GET    /api/auth.php?action=logout    → تسجيل الخروج
 * GET    /api/auth.php?action=status    → التحقق من حالة تسجيل الدخول وصلاحية الحساب
 */

// بدء الجلسة قبل أي مخرجات
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'register':
        if ($method !== 'POST') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }

        $data = getJsonInput();
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        // التحقق من المدخلات
        if (empty($email) || empty($password)) {
            jsonResponse(['error' => 'البريد الإلكتروني وكلمة المرور مطلوبان'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['error' => 'البريد الإلكتروني غير صالح'], 400);
        }

        if (strlen($password) < 6) {
            jsonResponse(['error' => 'يجب ألا تقل كلمة المرور عن 6 أحرف'], 400);
        }

        // التحقق من تكرار البريد الإلكتروني
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            jsonResponse(['error' => 'البريد الإلكتروني مسجل بالفعل'], 400);
        }

        // تشفير كلمة المرور وتحديد نهاية الفترة التجريبية (ساعة واحدة)
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $trialEnds = date('Y-m-d H:i:s', strtotime('+1 hour'));

        try {
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, trial_ends_at, subscription_status) VALUES (?, ?, ?, 'trial')");
            $stmt->execute([$email, $passwordHash, $trialEnds]);
            
            $userId = $pdo->lastInsertId();

            // حفظ الجلسة وتسجيل الدخول تلقائياً
            $_SESSION['user_id'] = $userId;
            $_SESSION['email'] = $email;

            jsonResponse([
                'success' => true,
                'message' => 'تم تسجيل الحساب بنجاح وعضوية تجريبية لمدة ساعة واحدة',

                'user' => [
                    'id' => $userId,
                    'email' => $email
                ]
            ], 201);

        } catch (PDOException $e) {
            jsonResponse(['error' => 'حدث خطأ أثناء التسجيل: ' . $e->getMessage()], 500);
        }
        break;

    case 'login':
        if ($method !== 'POST') {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }

        $data = getJsonInput();
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            jsonResponse(['error' => 'البريد الإلكتروني وكلمة المرور مطلوبان'], 400);
        }

        $stmt = $pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            jsonResponse(['error' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة'], 401);
        }

        // حفظ الجلسة
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];

        jsonResponse([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'user' => [
                'id' => $user['id'],
                'email' => $user['email']
            ]
        ]);
        break;

    case 'logout':
        // تدمير الجلسة بالكامل
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        jsonResponse(['success' => true, 'message' => 'تم تسجيل الخروج بنجاح']);
        break;

    case 'status':
        if (!isset($_SESSION['user_id'])) {
            jsonResponse(['logged_in' => false]);
        }

        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT id, email, trial_ends_at, subscription_status FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            session_destroy();
            jsonResponse(['logged_in' => false]);
        }

        // حساب الوقت المتبقي في الفترة التجريبية بدقة
        $now = time();
        $trialEndTs = strtotime($user['trial_ends_at']);
        $secondsLeft = $trialEndTs - $now;
        if ($secondsLeft < 0) $secondsLeft = 0;

        $daysLeft = ceil($secondsLeft / 86400);

        if ($secondsLeft >= 86400) {
            $days = ceil($secondsLeft / 86400);
            $timeLeftText = "$days يوم";
        } elseif ($secondsLeft >= 3600) {
            $hours = ceil($secondsLeft / 3600);
            $timeLeftText = "$hours ساعة";
        } else {
            $minutes = ceil($secondsLeft / 60);
            $timeLeftText = "$minutes دقيقة";
        }

        // تحديث حالة الحساب تلقائياً إذا انتهت الفترة التجريبية ولم يكن مفعلاً
        $status = $user['subscription_status'];
        if ($status === 'trial' && $secondsLeft <= 0) {
            $status = 'expired';
            $update = $pdo->prepare("UPDATE users SET subscription_status = 'expired' WHERE id = ?");
            $update->execute([$userId]);
        }

        jsonResponse([
            'logged_in' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'subscription_status' => $status,
                'trial_ends_at' => $user['trial_ends_at'],
                'days_left' => $daysLeft,
                'time_left_text' => $timeLeftText
            ]
        ]);

        break;

    default:
        jsonResponse(['error' => 'Action required or not supported'], 400);
}
