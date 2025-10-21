<?php
/**
 * Application Bootstrap File
 * Initializes Composer autoloader and core configuration
 */

// Prevent multiple inclusions
if (defined('BOOTSTRAP_LOADED')) {
    return;
}
define('BOOTSTRAP_LOADED', true);

// =============================================================================
// AUTOLOADER (COMPOSER)
// =============================================================================

$vendorPath = dirname(dirname(__DIR__)) . '/vendor/autoload.php';

if (!file_exists($vendorPath)) {
    die('خطا: Composer autoloader یافت نشد. لطفاً دستور "composer install" یا "composer dump-autoload -o" را اجرا کنید.');
}

require_once $vendorPath;

// =============================================================================
// CONFIGURATION
// =============================================================================

require_once __DIR__ . '/config.php';

// =============================================================================
// ERROR HANDLER (Optional - for better error display)
// =============================================================================

if (defined('DEBUG_MODE') && DEBUG_MODE && !is_cli()) {
    set_exception_handler(function($exception) {
        echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; margin: 20px; border-radius: 5px;">';
        echo '<h2 style="color: #721c24; margin: 0 0 10px 0;">خطا رخ داد</h2>';
        echo '<p style="color: #721c24; margin: 0;"><strong>پیام:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>';
        echo '<p style="color: #721c24; margin: 10px 0 0 0;"><strong>فایل:</strong> ' . htmlspecialchars($exception->getFile()) . ' (خط ' . $exception->getLine() . ')</p>';
        echo '<details style="margin-top: 15px;">';
        echo '<summary style="cursor: pointer; color: #721c24;">Stack Trace</summary>';
        echo '<pre style="background: #fff; padding: 10px; margin-top: 10px; border: 1px solid #ddd; overflow: auto;">';
        echo htmlspecialchars($exception->getTraceAsString());
        echo '</pre>';
        echo '</details>';
        echo '</div>';
    });
}

// =============================================================================
// INITIALIZE SESSION (if not in CLI)
// =============================================================================

if (!is_cli()) {
    // Use fully-qualified class name to avoid 'use' inside blocks
    if (class_exists('\\App\\Classes\\Session')) {
        \App\Classes\Session::start();
    } else {
        // Session class هنوز لود نشده — احتمالاً autoload مشکل دارد
        // نادیده می‌گیریم تا بررسی‌های بعدی انجام شود
    }
}

// =============================================================================
// READY TO USE
// =============================================================================

// At this point, you can use:
// - All PSR-4 autoloaded classes (App\Classes\*, App\Models\*)
// - All configuration constants (BASE_URL, DB_HOST, etc.)
// - Session management via App\Classes\Session
// - Helper functions (app_path(), app_url(), redirect(), etc.)
?>