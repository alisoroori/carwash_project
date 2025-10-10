<?php

class Config
{
    private static $config = [];
    private static $initialized = false;

    public static function init()
    {
        if (self::$initialized) {
            return;
        }

        // Load .env file
        $envPath = __DIR__ . '/../../.env';
        if (!file_exists($envPath)) {
            die('Environment file not found. Please create .env file.');
        }

        $envContent = file_get_contents($envPath);
        $lines = explode("\n", $envContent);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            self::$config[$key] = $value;
        }

        // Set PHP configuration
        ini_set('display_errors', self::get('APP_DEBUG', false));
        ini_set('session.cookie_httponly', self::get('SESSION_HTTP_ONLY', true));
        ini_set('session.cookie_secure', self::get('SESSION_SECURE', true));
        ini_set('session.gc_maxlifetime', self::get('SESSION_LIFETIME', 7200));

        self::$initialized = true;
    }

    public static function get($key, $default = null)
    {
        if (!self::$initialized) {
            self::init();
        }

        return self::$config[$key] ?? $default;
    }

    public static function isDevelopment()
    {
        return self::get('APP_ENV') === 'development';
    }

    public static function isProduction()
    {
        return self::get('APP_ENV') === 'production';
    }
}

// Initialize configuration
Config::init();

// Security settings
define('RATE_LIMIT_REQUESTS', 60);
define('RATE_LIMIT_PERIOD', 60);
define('PASSWORD_MIN_LENGTH', 8);
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Enable security headers in production
if (Config::isProduction()) {
    ini_set('session.cookie_httponly', true);
    ini_set('session.cookie_secure', true);
    ini_set('session.use_strict_mode', true);
}
