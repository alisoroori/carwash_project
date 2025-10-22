<?php
declare(strict_types=1);

namespace App\Classes;

class Config
{
    private static $variables = [];
    private static $isLoaded = false;
    
    // Load environment variables from .env
    public static function load()
    {
        if (self::$isLoaded) {
            return;
        }

        $envPath = __DIR__ . '/../../.env';
        $examplePath = __DIR__ . '/../../.env.example';
        
        if (!file_exists($envPath)) {
            if (file_exists($examplePath)) {
                die('خطا: فایل .env یافت نشد. لطفاً از .env.example یک کپی ایجاد کرده و مقادیر را تنظیم کنید.');
            } else {
                die('خطا: هیچ فایل .env یا .env.example یافت نشد.');
            }
        }
        
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) {
                continue;
            }
            
            list($name, $value) = explode('=', $line, 2) + ['', ''];
            $name = trim($name);
            $value = trim($value);
            
            if (!empty($name)) {
                self::$variables[$name] = $value;
            }
        }
        
        self::$isLoaded = true;
    }
    
    // Get environment variable with optional default
    public static function get($key, $default = null)
    {
        if (!self::$isLoaded) {
            self::load();
        }
        
        return isset(self::$variables[$key]) ? self::$variables[$key] : $default;
    }
    
    // Check production environment
    public static function isProduction()
    {
        return self::get('APP_ENV') === 'production';
    }
    
    // Check debug mode
    public static function isDebug()
    {
        return self::get('APP_DEBUG', 'false') === 'true';
    }
}

} // end App\Classes namespace
