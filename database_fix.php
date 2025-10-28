<?php
/**
 * Database Schema Fix for CarWash Registration
 * This script will add missing columns to the existing carwash_profiles table
 */

// Include database connection
require_once __DIR__ . '/backend/includes/db.php';

echo "<h2>CarWash Database Schema Fix</h2>";
echo "<p>Adding missing columns to match registration form...</p>";

try {
    $conn = getDBConnection();
    
    // List of columns to add to carwash_profiles table
    $columns_to_add = [
        "ADD COLUMN user_id INT(11) NULL",
        "ADD COLUMN tax_number VARCHAR(50) NULL",
        "ADD COLUMN license_number VARCHAR(50) NULL", 
        "ADD COLUMN owner_name VARCHAR(100) NULL",
        "ADD COLUMN owner_phone VARCHAR(20) NULL",
        "ADD COLUMN birth_date DATE NULL",
        "ADD COLUMN district VARCHAR(100) NULL",
        "ADD COLUMN exterior_price DECIMAL(10,2) DEFAULT 0",
        "ADD COLUMN interior_price DECIMAL(10,2) DEFAULT 0", 
        "ADD COLUMN detailing_price DECIMAL(10,2) DEFAULT 0",
        "ADD COLUMN opening_time TIME NULL",
        "ADD COLUMN closing_time TIME NULL",
        "ADD COLUMN capacity INT(11) DEFAULT 0",
        "ADD COLUMN profile_image VARCHAR(255) NULL",
        "ADD COLUMN logo_image VARCHAR(255) NULL",
        "ADD COLUMN status VARCHAR(20) DEFAULT 'pending'"
    ];
    
    echo "<h3>Adding columns to carwash_profiles table:</h3>";
    
    foreach ($columns_to_add as $column) {
        try {
            $sql = "ALTER TABLE carwash_profiles " . $column;
            $conn->exec($sql);
            echo "<p style='color: green;'>✓ Added: " . htmlspecialchars($column) . "</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<p style='color: orange;'>⚠ Column already exists: " . htmlspecialchars($column) . "</p>";
            } else {
                echo "<p style='color: red;'>✗ Error adding " . htmlspecialchars($column) . ": " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    // Now check if users table has required columns
    echo "<h3>Checking users table:</h3>";
    
    $users_columns = [
        "ADD COLUMN username VARCHAR(50) UNIQUE NULL",
        "ADD COLUMN full_name VARCHAR(100) NULL"
    ];
    
    foreach ($users_columns as $column) {
        try {
            $sql = "ALTER TABLE users " . $column;
            $conn->exec($sql);
            echo "<p style='color: green;'>✓ Added to users: " . htmlspecialchars($column) . "</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<p style='color: orange;'>⚠ Column already exists in users: " . htmlspecialchars($column) . "</p>";
            } else {
                echo "<p style='color: red;'>✗ Error adding to users " . htmlspecialchars($column) . ": " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    echo "<h3>✅ Schema update completed!</h3>";
    echo "<p><a href='backend/auth/Car_Wash_Registration.php'>→ Go to Registration Form</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
