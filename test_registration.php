<?php
session_start();
require_once 'backend/includes/db.php';

echo "<h2>Testing Customer Registration Process</h2>";

try {
    $conn = getDBConnection();
    echo "✅ Database connection successful<br>";
    
    // Check if tables exist
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->fetch()) {
        echo "✅ Users table exists<br>";
    }
    
    $stmt = $conn->query("SHOW TABLES LIKE 'customer_profiles'");
    if ($stmt->fetch()) {
        echo "✅ Customer profiles table exists<br>";
    }
    
    // Check users table structure
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Users table columns: " . implode(', ', $columns) . "<br>";
    
    echo "<br><strong>Database is ready for customer registration!</strong><br>";
    echo "<a href='backend/auth/Customer_Registration.php'>Try Customer Registration</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>