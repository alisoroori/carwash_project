<?php
// 🧩 جلوگیری از هر نوع خروجی پیش از شروع سشن
ob_start();

// ✅ شروع سشن فقط اگر هنوز شروع نشده
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ تنظیمات فقط وقتی که هنوز هیچ هِدری ارسال نشده
if (!headers_sent()) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
}

/**
 * 🌐 Configuration Class
 */
class Config
{
    private static $config = [];
    private static $initialized = false;

    public static function init()
    {
        if (self::$initialized) return;
        self::$initialized = true;

        $envPath = __DIR__ . '/../../.env';

        // 📦 اگر فایل .env نیست، از مقادیر پیش‌فرض استفاده می‌شود
        if (!file_exists($envPath)) {
            self::setDefaults();
            return;
        }

        // 🔍 خواندن فایل .env
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if ($key !== '') {
                self::$config[$key] = $value;
            }
        }

        self::setDefaults();

        // ⚙️ تنظیمات PHP
        if (!headers_sent()) {
            ini_set('display_errors', self::get('APP_DEBUG', false) ? '1' : '0');
            ini_set('session.cookie_httponly', self::get('SESSION_HTTP_ONLY', true) ? '1' : '0');
            ini_set('session.cookie_secure', self::get('SESSION_SECURE', false) ? '1' : '0');
            ini_set('session.gc_maxlifetime', self::get('SESSION_LIFETIME', 7200));
        }
    }

    private static function setDefaults()
    {
        $defaults = [
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'carwash_db',
            'DB_PORT' => '3307',
            'DB_USER' => 'root',
            'DB_PASS' => '',
            'APP_ENV' => 'development',
            'APP_URL' => 'http://localhost/carwash_project',
            'APP_NAME' => 'CarWash Management System',
            'APP_DEBUG' => 'true',
            'SESSION_LIFETIME' => '7200',
            'SESSION_HTTP_ONLY' => 'true',
            'SESSION_SECURE' => 'false'
        ];

        foreach ($defaults as $key => $value) {
            if (!isset(self::$config[$key])) {
                self::$config[$key] = $value;
            }
        }
    }

    public static function get($key, $default = null)
    {
        if (!self::$initialized) self::init();
        return self::$config[$key] ?? $default;
    }

    public static function isDevelopment()
    {
        return strtolower(self::get('APP_ENV')) === 'development';
    }

    public static function isProduction()
    {
        return strtolower(self::get('APP_ENV')) === 'production';
    }
}

// 🟢 مقداردهی اولیه
Config::init();

// 🛡️ ثابت‌های امنیتی
define('RATE_LIMIT_REQUESTS', 60);
define('RATE_LIMIT_PERIOD', 60);
define('PASSWORD_MIN_LENGTH', 8);
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// 🔒 فعال‌سازی تنظیمات خاص فقط در محیط production
if (Config::isProduction() && !headers_sent()) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.use_strict_mode', '1');
}

// ✅ پایان خروجی بافر (مطمئن می‌شیم قبل از redirect یا header بسته شده)
ob_end_clean();
