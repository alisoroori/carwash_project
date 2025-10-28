<?php
session_start();
require_once 'backend/includes/db.php';

echo "<h2>Testing Welcome Page System</h2>";

try {
    $conn = getDBConnection();
    echo "✅ Database connection successful<br>";
    
    // Simulate a successful registration session
    echo "<h3>Simulating Registration Success:</h3>";
    $_SESSION['user_id'] = 999;
    $_SESSION['user_name'] = 'Test User';
    $_SESSION['user_email'] = 'test@example.com';
    $_SESSION['role'] = 'customer';
    $_SESSION['registration_success'] = true;
    
    echo "✅ Registration session variables set<br>";
    echo "✅ Welcome page flag activated<br>";
    
    echo "<h3>Test Links:</h3>";
    echo "<a href='backend/auth/welcome.php' target='_blank' class='test-link'>🎉 Test Welcome Page</a><br><br>";
    
    echo "<h3>Expected Behavior:</h3>";
    echo "1. ✅ Welcome page shows with user name and animations<br>";
    echo "2. ✅ Auto-redirect countdown starts (5 seconds)<br>";
    echo "3. ✅ Registration success flag is cleared after viewing<br>";
    echo "4. ✅ User is redirected to customer dashboard<br>";
    echo "5. ✅ Subsequent visits to welcome.php redirect to login<br>";
    
    echo "<h3>Flow Test:</h3>";
    echo "<a href='backend/auth/Customer_Registration.php' target='_blank' class='test-link'>📝 Try Full Registration Process</a><br><br>";
    
    echo "<div style='background: #f0f9ff; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<strong>🎯 Complete Registration Flow:</strong><br>";
    echo "1. Fill out registration form<br>";
    echo "2. Get redirected to welcome page<br>";
    echo "3. See welcome message with auto-redirect<br>";
    echo "4. Land on customer dashboard<br>";
    echo "5. Try visiting welcome.php again (should redirect to login)<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

<style>
.test-link {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 8px;
    margin: 5px;
    font-weight: bold;
    transition: transform 0.2s;
}
.test-link:hover {
    transform: translateY(-2px);
}
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h2 {
    color: #2563eb;
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 10px;
}
h3 {
    color: #059669;
    margin-top: 25px;
}
</style>
