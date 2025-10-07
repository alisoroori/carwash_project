<?php

/**
 * Main Configuration File
 * Contains database connection and application settings
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_NAME', 'carwash_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application URLs and Paths
define('BASE_URL', 'http://localhost/carwash_project');
define('FRONTEND_URL', BASE_URL . '/frontend');
define('BACKEND_URL', BASE_URL . '/backend');
define('API_URL', BACKEND_URL . '/api');

// File System Paths
define('ROOT_PATH', dirname(dirname(dirname(__FILE__))));
define('UPLOADS_PATH', ROOT_PATH . '/backend/uploads');
define('LOGS_PATH', ROOT_PATH . '/backend/logs');

// Session Configuration
define('SESSION_NAME', 'carwash_session');
define('SESSION_LIFETIME', 3600); // 1 hour
session_name(SESSION_NAME);

// Application Settings
define('APP_NAME', 'CarWash Management System');
define('APP_VERSION', '1.0.0');
define('DEBUG_MODE', true);
define('TIMEZONE', 'UTC');

// Upload Settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// Business Rules
define('MIN_BOOKING_NOTICE', 2); // hours
define('MAX_BOOKING_ADVANCE', 30); // days
define('CANCELLATION_WINDOW', 24); // hours

// Error Handling Setup
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone Setup
date_default_timezone_set(TIMEZONE);

// Create required directories
$directories = [UPLOADS_PATH, LOGS_PATH];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}
