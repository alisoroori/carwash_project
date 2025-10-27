<?php declare(strict_types=1);
/**
 * Application Configuration File
 * Central configuration for database, paths, and application settings
 * 
 * @package CarWash
 */

namespace { // global namespace block starts here

// Prevent direct access
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// =============================================================================
// DATABASE CONFIGURATION
// =============================================================================

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'carwash_db'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// =============================================================================
// APPLICATION PATHS
// =============================================================================

// Root directory (adjust if project is in subdirectory)
define('ROOT_PATH', dirname(dirname(__DIR__)));
// Backend paths
define('BACKEND_PATH', ROOT_PATH . '/backend');
define('CLASSES_PATH', BACKEND_PATH . '/classes');
define('MODELS_PATH', BACKEND_PATH . '/models');
define('INCLUDES_PATH', BACKEND_PATH . '/includes');
define('AUTH_PATH', BACKEND_PATH . '/auth');
define('API_PATH', BACKEND_PATH . '/api');
define('DASHBOARD_PATH', BACKEND_PATH . '/dashboard');

// Frontend paths
define('FRONTEND_PATH', ROOT_PATH . '/frontend');
define('CSS_PATH', FRONTEND_PATH . '/css');
define('JS_PATH', FRONTEND_PATH . '/js');

// Upload paths
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('PROFILE_UPLOAD_PATH', AUTH_PATH . '/uploads/profiles');
define('SERVICE_UPLOAD_PATH', UPLOAD_PATH . '/services');

// Vendor path
define('VENDOR_PATH', ROOT_PATH . '/vendor');

// =============================================================================
// APPLICATION URLS
// =============================================================================

// Base URL (change this for production)
define('BASE_URL', env('APP_URL', 'http://localhost/carwash_project'));

// Backend URLs
define('BACKEND_URL', BASE_URL . '/backend');
define('AUTH_URL', BACKEND_URL . '/auth');
define('API_URL', BACKEND_URL . '/api');
define('DASHBOARD_URL', BACKEND_URL . '/dashboard');

// Frontend URLs
define('FRONTEND_URL', BASE_URL . '/frontend');
define('CSS_URL', FRONTEND_URL . '/css');
define('JS_URL', FRONTEND_URL . '/js');

// Upload URLs
define('UPLOAD_URL', BASE_URL . '/uploads');
define('PROFILE_UPLOAD_URL', AUTH_URL . '/uploads/profiles');
define('SERVICE_UPLOAD_URL', UPLOAD_URL . '/services');

// =============================================================================
// APPLICATION SETTINGS
// =============================================================================

// Environment (development | production)
define('APP_ENV', 'development');

// Debug mode
define('DEBUG_MODE', APP_ENV === 'development');

// Timezone
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Tehran'));

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
}

// =============================================================================
// SESSION CONFIGURATION
// =============================================================================

define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 7200)); // 2 hours in seconds
define('SESSION_NAME', 'carwash_session');

// =============================================================================
// UPLOAD SETTINGS
// =============================================================================

define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// =============================================================================
// PAGINATION
// =============================================================================

define('ITEMS_PER_PAGE', 10);

// =============================================================================
// PAYMENT GATEWAY (iyzico)
// =============================================================================

define('IYZICO_API_KEY', 'sandbox-your-api-key'); // Change in production
define('IYZICO_SECRET_KEY', 'sandbox-your-secret-key'); // Change in production
define('IYZICO_BASE_URL', 'https://sandbox-api.iyzipay.com'); // Change in production

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Get full path from relative path
 */
function app_path($path = '') {
    return ROOT_PATH . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Get full URL from relative path
 */
function app_url($path = '') {
    return BASE_URL . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Check if running in CLI mode
 */
function is_cli() {
    return php_sapi_name() === 'cli';
}

/**
 * Check if request is AJAX
 */
function is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get current URL
 */
function current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Redirect to URL
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

} // end global namespace

namespace App\Classes {

/**
 * Environment configuration handler
 * Loads variables from .env file and provides secure access
 */
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

namespace { 

/**
 * Configuration file - uses environment variables from .env
 */

use App\Classes\Config;

// Helper function for backward compatibility
function env($key, $default = null) {
    return Config::get($key, $default);
}

// Protect against multiple defines when this file is included more than once
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(__DIR__ . '/..'));
}

// Guard common application constants to avoid "already defined" warnings
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/carwash_project');
}

if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'development');
}

// Database defaults (only if not set elsewhere)
if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'carwash');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// Session / upload / other commonly redefined constants
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', ROOT_PATH . '/uploads');
}
if (!defined('PROFILE_UPLOAD_DIR')) {
    define('PROFILE_UPLOAD_DIR', ROOT_PATH . '/backend/auth/uploads/profiles');
}
if (!defined('LOG_DIR')) {
    define('LOG_DIR', ROOT_PATH . '/logs');
}

// Management URL for phpMyAdmin (points to the carwash_db database)
if (!defined('DB_ADMIN_URL')) {
    define('DB_ADMIN_URL', 'http://localhost/phpmyadmin/index.php?route=/database/structure&db=carwash_db');
}

// Ensure error display is suppressed on non-dev environments
if (defined('APP_ENV') && APP_ENV !== 'development') {
    @ini_set('display_errors', '0');
}

} // end global namespace
