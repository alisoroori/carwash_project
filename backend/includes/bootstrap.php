<?php
/**
 * Bootstrap file for initializing autoloading and core functionality
 */

// Define root path
define('ROOT_PATH', dirname(__DIR__, 2));

// Set up Composer autoloading
require_once ROOT_PATH . '/vendor/autoload.php';

// Load environment variables from .env file
if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
    $dotenv->safeLoad(); // Doesn't throw exception if .env doesn't exist
}

// Load configuration
require_once __DIR__ . '/config.php';

// For backward compatibility with legacy code
if (file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
}
if (file_exists(__DIR__ . '/functions.php')) {
    require_once __DIR__ . '/functions.php';
}

// Minimal bootstrap: autoload, config, logger, error/exception handlers
require_once __DIR__ . '/../../vendor/autoload.php';

// Optional project config (defines APP_ENV etc.)
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// Initialize logger
use App\Classes\Logger;
Logger::init();

// Determine environment: prefer constant APP_ENV, then env var, else development
$env = defined('APP_ENV') ? APP_ENV : (getenv('APP_ENV') ?: 'development');

// Production settings
if (strtolower($env) === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// Convert warnings/notices to ErrorException to centralize handling
set_error_handler(function ($severity, $message, $file, $line) {
    // Respect @ operator
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

// Central exception handler: log full trace, show generic message (JSON for API)
set_exception_handler(function ($e) {
    \App\Classes\Logger::exception($e);

    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '';

    if (stripos($accept, 'application/json') !== false || stripos($uri, '/api/') !== false) {
        header('Content-Type: application/json', true, 500);
        echo json_encode(['success' => false, 'message' => 'An internal error occurred.']);
    } else {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo 'An internal error occurred. Please try again later.';
    }
    exit;
});