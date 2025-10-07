<?php

/**
 * Main Configuration File
 * 
 * Contains all configuration constants for the CarWash project
 * WARNING: Do not commit sensitive information like API keys to version control
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_NAME', 'carwash_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'CarWash Management System');
define('APP_URL', 'http://localhost/carwash_project');
define('APP_VERSION', '1.0.0');

// Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'carwash_session');

// API Configuration
define('API_VERSION', 'v1');
define('JWT_SECRET', 'your-secret-key-here'); // Change in production

// SMTP Configuration
define('SMTP_HOST', 'smtp.example.com'); // Replace with your SMTP host
define('SMTP_PORT', 587); // Replace with your SMTP port

// Error Reporting
define('DEBUG_MODE', true);
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Create a local config file if it doesn't exist
$localConfigFile = __DIR__ . '/config.local.php';
if (!file_exists($localConfigFile)) {
    copy(__FILE__, $localConfigFile);
}

// Load local configuration overrides if they exist
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
}

// In other PHP files
require_once __DIR__ . '/includes/config.php';

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// File upload
if ($_FILES['image']['size'] > MAX_FILE_SIZE) {
    throw new Exception('File too large');
}

// Email sending
$mailer->Host = SMTP_HOST;
$mailer->Port = SMTP_PORT;

// Local configuration overrides
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
}