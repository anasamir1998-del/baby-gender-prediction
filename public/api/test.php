<?php
/**
 * ملف تشخيص - احذفه بعد ما تحل المشكلة!
 */
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'step1_php' => 'PHP يعمل ✅',
    'php_version' => phpversion(),
    'pdo_available' => extension_loaded('pdo_mysql') ? 'نعم ✅' : 'لا ❌',
    'curl_available' => extension_loaded('curl') ? 'نعم ✅' : 'لا ❌',
], JSON_UNESCAPED_UNICODE);

echo "\n\n--- اختبار الاتصال بقاعدة البيانات ---\n";

$DB_HOST = 'localhost';
$DB_NAME = 'u123456789_baby';
$DB_USER = 'u123456789_admin';
$DB_PASS = 'YOUR_PASSWORD_HERE';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS
    );
    echo json_encode(['step2_db' => 'اتصال قاعدة البيانات ناجح ✅'], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode([
        'step2_db' => 'فشل الاتصال ❌',
        'error' => $e->getMessage(),
        'hint' => 'تأكد من بيانات DB_HOST, DB_NAME, DB_USER, DB_PASS في config.php'
    ], JSON_UNESCAPED_UNICODE);
}
