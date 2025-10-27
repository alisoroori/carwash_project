<?php

/**
 * Database Configuration File
 * Contains database credentials and connection settings
 */

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'carwash_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');

// Database Charset and Collation
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// Connection Options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

// Connection timeout settings
define('DB_CONNECT_TIMEOUT', 5);  // seconds
define('DB_READ_TIMEOUT', 30);    // seconds

// Maximum connections
define('DB_MAX_CONNECTIONS', 100);

try {
    // Create connection string
    $dsn = sprintf(
        "mysql:host=%s;port=%s;dbname=%s;charset=%s",
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    // Initialize database connection
    $conn = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);

    // Set timeouts
    $conn->setAttribute(PDO::ATTR_TIMEOUT, DB_CONNECT_TIMEOUT);

    // Test connection
    $conn->query("SELECT 1");
} catch (PDOException $e) {
    // Log error and display generic message
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}

require_once 'db_config.php';
try {
    $result = $conn->query("SELECT NOW() as time")->fetch();
    echo "Connected successfully. Server time: " . $result['time'];
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
