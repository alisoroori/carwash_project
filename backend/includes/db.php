<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\includes\db.php

/**
 * CarWash Database Configuration and Connection Handler
 * Following project conventions for file-based routing and modular structure
 */

// Database credentials - using standard XAMPP port 3306
define('DB_HOST', 'localhost');
define('DB_NAME', 'carwash_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get database connection using PDO
 * Returns configured PDO instance for database operations
 */
function getDBConnection()
{
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Verify database tables exist and are accessible
 * Used for system health checks
 */
function verifyDatabaseTables()
{
    $requiredTables = ['users', 'carwashes', 'services', 'bookings', 'reviews', 'settings'];
    $conn = getDBConnection();

    foreach ($requiredTables as $table) {
        // Fix: Use direct query instead of prepared statement for SHOW TABLES
        $query = "SHOW TABLES LIKE '" . $table . "'";
        $stmt = $conn->query($query);

        if (!$stmt->fetch()) {
            throw new Exception("Required table '$table' not found in database");
        }
    }

    return true;
}

/**
 * Get database table information
 * Useful for admin dashboard statistics
 */
function getDatabaseStats()
{
    $conn = getDBConnection();
    $stats = [];

    $tables = ['users', 'carwashes', 'services', 'bookings', 'reviews', 'settings'];

    foreach ($tables as $table) {
        // Use direct query to avoid SQL syntax issues
        $query = "SELECT COUNT(*) as count FROM `" . $table . "`";
        $stmt = $conn->query($query);
        $result = $stmt->fetch();
        $stats[$table] = $result['count'];
    }

    return $stats;
}

// Test connection endpoint (remove in production)
if (isset($_GET['test_db'])) {
    try {
        $conn = getDBConnection();
        echo "✅ Database connected successfully on port " . DB_PORT . "!<br>";

        if (isset($_GET['verify_tables'])) {
            verifyDatabaseTables();
            echo "✅ All required tables verified!<br>";

            $stats = getDatabaseStats();
            echo "<h3>Database Statistics:</h3>";
            echo "<ul>";
            foreach ($stats as $table => $count) {
                echo "<li>$table: $count records</li>";
            }
            echo "</ul>";
        }
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage();
    }
}

// Health check function for monitoring
if (isset($_GET['health_check'])) {
    header('Content-Type: application/json');
    try {
        $conn = getDBConnection();

        // Simple table check without verification
        $stats = [];
        $tables = ['users', 'carwashes', 'services', 'bookings', 'reviews', 'settings'];

        foreach ($tables as $table) {
            try {
                $query = "SELECT COUNT(*) as count FROM `" . $table . "`";
                $stmt = $conn->query($query);
                $result = $stmt->fetch();
                $stats[$table] = $result['count'];
            } catch (Exception $e) {
                $stats[$table] = 'error';
            }
        }

        echo json_encode([
            'status' => 'healthy',
            'database' => DB_NAME,
            'port' => DB_PORT,
            'tables' => $stats,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

/**
 * Legacy database connection file (for compatibility with old code)
 * Uses modern Database class to avoid code duplication
 */

// Load autoloader if not already loaded
$autoloaderPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($autoloaderPath)) {
    require_once $autoloaderPath;
}

// Load config.php if not already defined
if (!defined('DB_HOST') && file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// Establish database connection using mysqli (for compatibility with old code)
// Behind the scenes, uses modern Database class
try {
    if (class_exists('App\\Classes\\Database')) {
        // Use modern Database class
        $db = App\Classes\Database::getInstance();
        $pdo = $db->getPdo();
        
        // Create a mysqli connection for compatibility with old code
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$conn) {
            throw new Exception("Database connection error: " . mysqli_connect_error());
        }
        
        // Set charset for mysqli connection
        mysqli_set_charset($conn, 'utf8mb4');
        
        // Helper function for safe mysqli queries using PDO (for old code compatibility)
        function mysqli_query_safe($conn, $query, $params = []) {
            $db = App\Classes\Database::getInstance();
            
            try {
                if (empty($params)) {
                    // For queries without parameters
                    return $db->query($query);
                } else {
                    // For parameterized queries
                    $stmt = $db->prepare($query);
                    $stmt->execute($params);
                    return $stmt;
                }
            } catch (Exception $e) {
                error_log('Query failed: ' . $e->getMessage() . ' - Query: ' . $query);
                return false;
            }
        }
        
    } else {
        // If Database class is not available, use direct mysqli connection
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (!$conn) {
            throw new Exception("Database connection error: " . mysqli_connect_error());
        }
        
        // Set charset for connection
        mysqli_set_charset($conn, 'utf8mb4');
    }
} catch (Exception $e) {
    // Log error
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection error. Please contact the system administrator.');
}
