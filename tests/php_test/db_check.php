<?php
// filepath: c:\xampp\htdocs\carwash_project\db_check.php

require_once __DIR__ . '/vendor/autoload.php';

// Load config directly
$config = require_once __DIR__ . '/backend/includes/config.php';

echo "<h2>Database Configuration Check</h2>";
echo "<pre>";
echo "Host: " . $config['db_host'] . "\n";
echo "Database: " . $config['db_name'] . "\n";
echo "Username: " . $config['db_user'] . "\n";
echo "Password: " . (empty($config['db_pass']) ? "(empty)" : "(set)") . "\n";
echo "</pre>";

// Try manual PDO connection
try {
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='color:green;font-weight:bold;'>✅ Direct PDO connection successful!</div>";
    
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Database Tables:</h3>";
    echo "<pre>";
    if (count($tables) > 0) {
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    } else {
        echo "No tables found. Database may be empty.\n";
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<div style='color:red;font-weight:bold;'>❌ Connection failed: " . $e->getMessage() . "</div>";
}

// Try connection using the Database class
echo "<h2>Testing Database Class Connection</h2>";
try {
    $db = App\Classes\Database::getInstance();
    echo "<div style='color:green;font-weight:bold;'>✅ Database class connection successful!</div>";
    
    // Test a simple query
    $result = $db->fetchOne("SELECT VERSION() as version");
    echo "<pre>";
    echo "MySQL Version: " . $result['version'] . "\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<div style='color:red;font-weight:bold;'>❌ Database class error: " . $e->getMessage() . "</div>";
    
    // Display Database class implementation for review
    echo "<h3>Review Your Database Class Implementation:</h3>";
    if (file_exists(__DIR__ . '/backend/classes/Database.php')) {
        echo "<pre style='background-color:#f5f5f5;padding:10px;'>";
        highlight_file(__DIR__ . '/backend/classes/Database.php');
        echo "</pre>";
    } else {
        echo "<div style='color:red;'>Database.php not found in expected location.</div>";
    }
}
?>