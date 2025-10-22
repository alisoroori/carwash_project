<?php
// فایل راه‌انداز اصلی برای لود کردن اتولودر و کلاس‌های اصلی

// مسیر کامل به ریشه پروژه
define('ROOT_PATH', dirname(__DIR__, 2));

// راه‌اندازی اتولودر Composer
require_once ROOT_PATH . '/vendor/autoload.php';

// بارگذاری تنظیمات
if (file_exists(ROOT_PATH . '/backend/includes/config.php')) {
    require_once ROOT_PATH . '/backend/includes/config.php';
}

// برای حفظ سازگاری با کدهای قدیمی
// فایل‌های قدیمی را در صورت نیاز وارد کنید
if (file_exists(ROOT_PATH . '/backend/includes/db.php')) {
    require_once ROOT_PATH . '/backend/includes/db.php';
}
if (file_exists(ROOT_PATH . '/backend/includes/functions.php')) {
    require_once ROOT_PATH . '/backend/includes/functions.php';
}