<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Configuration Management Class
 */
class Config
{
    /**
     * Get config value from environment
     * 
     * @param string $key Config key
     * @param mixed $default Default value if key not found
     * @return mixed Config value or default
     */
    public static function get(string $key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        
        // Convert specific strings to their proper types
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }
        
        return $value;
    }
    
    /**
     * Check if application is in production environment
     * 
     * @return bool
     */
    public static function isProduction(): bool
    {
        return strtolower(self::get('APP_ENV', 'production')) === 'production';
    }
    
    /**
     * Check if application is in development environment
     * 
     * @return bool
     */
    public static function isDevelopment(): bool
    {
        return strtolower(self::get('APP_ENV', 'production')) === 'development';
    }
    
    /**
     * Check if application is in testing environment
     * 
     * @return bool
     */
    public static function isTesting(): bool
    {
        return strtolower(self::get('APP_ENV', 'production')) === 'testing';
    }
    
    /**
     * Get debug status
     * 
     * @return bool
     */
    public static function isDebug(): bool
    {
        return (bool) self::get('APP_DEBUG', false);
    }
    
    /**
     * Load a specific configuration file
     * 
     * @param string $file Config file name
     * @return array Config values
     */
    public static function load(string $file = null): array
    {
        static $configs = [];
        
        if ($file !== null && isset($configs[$file])) {
            return $configs[$file];
        }
        
        if ($file === null) {
            return $configs;
        }
        
        $path = ROOT_PATH . '/backend/config/' . $file . '.php';
        
        if (file_exists($path)) {
            $configs[$file] = require $path;
            return $configs[$file];
        }
        
        return [];
    }
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'carwash_db');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default XAMPP has no password

// Application settings
define('APP_URL', 'http://localhost/carwash_project');
define('APP_NAME', 'CarWash Management System');

return [
    'db_host' => DB_HOST,
    'db_name' => DB_NAME,
    'db_user' => DB_USER,
    'db_pass' => DB_PASS,
    
    'app_url' => APP_URL,
    'app_name' => APP_NAME,
];
