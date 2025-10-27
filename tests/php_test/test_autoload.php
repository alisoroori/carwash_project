<?php
// Simple autoload & core classes test (CLI)
require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;
use App\Classes\Session;

echo "شروع تست اتولود...\n";

try {
    // Database test
    $db = Database::getInstance();
    $conn = $db->getConnection();
    if ($conn instanceof PDO) {
        echo "✅ اتصال به دیتابیس برقرار شد.\n";
    } else {
        echo "❌ اتصال به دیتابیس برقرار نشد (object not PDO).\n";
    }
} catch (\Throwable $e) {
    echo "❌ خطا در اتصال به دیتابیس: " . $e->getMessage() . "\n";
    exit(1);
}

// Session test
Session::start();
if (Session::isLoggedIn()) {
    echo "✅ سشن فعال و کاربر وارد شده است (user_id=" . Session::getUserId() . ").\n";
} else {
    echo "✅ سشن شروع شد (هیچ کاربری وارد نشده است).\n";
}

echo "✅ Autoloader کار می‌کند — کلاس‌ها بارگذاری شدند: App\\Classes\\Database, App\\Classes\\Session\n";
exit(0);
?>
