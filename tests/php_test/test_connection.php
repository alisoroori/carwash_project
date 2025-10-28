<?php
require_once 'backend/includes/db.php';

try {
    $conn = getDBConnection();
    echo "✅ Database connection successful!<br>";
    
    // Test a simple query
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Users table accessible - " . $result['count'] . " users found<br>";
    
    echo "✅ Database configuration is working correctly!";
} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>
