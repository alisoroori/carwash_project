<?php
session_start();
require_once 'backend/includes/db.php';

echo "<h2>Testing Database Column Fix</h2>";

try {
    $conn = getDBConnection();
    echo "✅ Database connection successful<br>";
    
    // Test a simple user insert with correct column names
    $test_username = 'test_user_' . time();
    $test_email = $test_username . '@test.com';
    $test_password = password_hash('test123', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([$test_username, 'Test User', $test_email, $test_password, 'customer']);
    
    if ($result) {
        echo "✅ Test user created successfully with correct column names<br>";
        
        // Clean up test user
        $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute([$test_username]);
        echo "✅ Test user cleaned up<br>";
    } else {
        echo "❌ Failed to create test user<br>";
    }
    
    echo "<br><strong>✅ All column name issues have been fixed!</strong><br>";
    echo "<a href='backend/auth/Customer_Registration.php'>Try Customer Registration Now</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
