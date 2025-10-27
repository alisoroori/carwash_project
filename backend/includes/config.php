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

// Prevent re-initialization when the file is included multiple times
if (defined('CONFIG_INITIALIZED')) {
    return;
}
define('CONFIG_INITIALIZED', true);

// =============================================================================
// DATABASE CONFIGURATION (guarded)
// =============================================================================

if (!defined('DB_HOST')) {
    define('DB_HOST', env('DB_HOST', 'localhost'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', env('DB_NAME', 'carwash_db'));
}
if (!defined('DB_USER')) {
    define('DB_USER', env('DB_USER', 'root'));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', env('DB_PASS', ''));
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// =============================================================================
// APPLICATION PATHS
// =============================================================================

// Root directory (adjust if project is in subdirectory)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(dirname(__DIR__)));
}

// Backend paths
if (!defined('BACKEND_PATH')) {
    define('BACKEND_PATH', ROOT_PATH . '/backend');
}
if (!defined('CLASSES_PATH')) {
    define('CLASSES_PATH', BACKEND_PATH . '/classes');
}
if (!defined('MODELS_PATH')) {
    define('MODELS_PATH', BACKEND_PATH . '/models');
}
if (!defined('INCLUDES_PATH')) {
    define('INCLUDES_PATH', BACKEND_PATH . '/includes');
}
if (!defined('AUTH_PATH')) {
    define('AUTH_PATH', BACKEND_PATH . '/auth');
}
if (!defined('API_PATH')) {
    define('API_PATH', BACKEND_PATH . '/api');
}
if (!defined('DASHBOARD_PATH')) {
    define('DASHBOARD_PATH', BACKEND_PATH . '/dashboard');
}

// Frontend paths
if (!defined('FRONTEND_PATH')) {
    define('FRONTEND_PATH', ROOT_PATH . '/frontend');
}
if (!defined('CSS_PATH')) {
    define('CSS_PATH', FRONTEND_PATH . '/css');
}
if (!defined('JS_PATH')) {
    define('JS_PATH', FRONTEND_PATH . '/js');
}

// Upload paths
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', ROOT_PATH . '/uploads');
}
if (!defined('PROFILE_UPLOAD_PATH')) {
    define('PROFILE_UPLOAD_PATH', AUTH_PATH . '/uploads/profiles');
}
if (!defined('SERVICE_UPLOAD_PATH')) {
    define('SERVICE_UPLOAD_PATH', UPLOAD_PATH . '/services');
}

// Vendor path
if (!defined('VENDOR_PATH')) {
    define('VENDOR_PATH', ROOT_PATH . '/vendor');
}

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

// Prevent multiple evaluation and duplicate-define warnings
if (!defined('CONFIG_INITIALIZED')) {
    define('CONFIG_INITIALIZED', true);
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

// Database defaults (read from environment/secrets when available)
// Prefer explicit environment variables (secrets in CI) and fall back to .env / defaults
$env_db_host = getenv('DB_HOST');
$env_db_name = getenv('DB_NAME');
$env_db_user = getenv('DB_USER');
$env_db_pass = getenv('DB_PASS');
// Support sentinel values from CI secrets to indicate an explicit empty password
// e.g., set secret to 'empty' or 'no_password' to indicate an empty string
if ($env_db_pass !== false && ($env_db_pass === 'empty' || $env_db_pass === 'no_password')) {
    $env_db_pass = '';
}

if (!defined('DB_HOST')) {
    if ($env_db_host !== false && $env_db_host !== '') {
        define('DB_HOST', $env_db_host);
    } else {
        define('DB_HOST', '127.0.0.1');
    }
}

if (!defined('DB_NAME')) {
    if ($env_db_name !== false && $env_db_name !== '') {
        define('DB_NAME', $env_db_name);
    } else {
        define('DB_NAME', env('DB_NAME', 'carwash_db'));
    }
}

if (!defined('DB_USER')) {
    if ($env_db_user !== false && $env_db_user !== '') {
        define('DB_USER', $env_db_user);
    } else {
        define('DB_USER', env('DB_USER', 'root'));
    }
}

if (!defined('DB_PASS')) {
    if ($env_db_pass !== false) {
        // allow empty string password
        define('DB_PASS', $env_db_pass);
    } else {
        define('DB_PASS', env('DB_PASS', ''));
    }
}

if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// Add admin/management URL for quick access to phpMyAdmin for this DB
if (!defined('DB_ADMIN_URL')) {
    define('DB_ADMIN_URL', 'http://localhost/phpmyadmin/index.php?route=/database/structure&db=carwash_db');
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

// Ensure error display is suppressed on non-dev environments
if (defined('APP_ENV') && APP_ENV !== 'development') {
	@ini_set('display_errors', '0');
}

} // end global namespace
