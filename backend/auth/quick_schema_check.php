<?php
// Quick database schema checker
require_once __DIR__ . '/../includes/db.php';

try {
    $conn = getDBConnection();
    echo "<h2>🔍 Database Schema Check</h2>";
    
    // Check users table structure
    echo "<h3>Users Table:</h3>";
    try {
        $stmt = $conn->query("DESCRIBE users");
        $users_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p><strong>Columns:</strong> " . implode(', ', $users_columns) . "</p>";
        
        $required_user_columns = ['username', 'full_name', 'password'];
        $missing_user_columns = array_diff($required_user_columns, $users_columns);
        
        if (empty($missing_user_columns)) {
            echo "<p style='color: green;'>✅ All required user columns exist</p>";
        } else {
            echo "<p style='color: red;'>❌ Missing columns: " . implode(', ', $missing_user_columns) . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error checking users table: " . $e->getMessage() . "</p>";
    }
    
    // Check carwash table structure  
    echo "<h3>Carwash Table:</h3>";
    try {
        $stmt = $conn->query("DESCRIBE carwash");
        $carwash_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p><strong>Columns:</strong> " . implode(', ', $carwash_columns) . "</p>";
        
        $required_carwash_columns = ['user_id', 'name', 'tax_number', 'license_number', 'owner_name', 'owner_id', 'city', 'district', 'address'];
        $missing_carwash_columns = array_diff($required_carwash_columns, $carwash_columns);
        
        if (empty($missing_carwash_columns)) {
            echo "<p style='color: green;'>✅ All required carwash columns exist</p>";
        } else {
            echo "<p style='color: red;'>❌ Missing columns: " . implode(', ', $missing_carwash_columns) . "</p>";
            echo "<h4>🔧 Quick Fix Available:</h4>";
            echo "<a href='apply_schema_fix.php' style='background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Apply Schema Fix</a>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error checking carwash table: " . $e->getMessage() . "</p>";
    }
    
    // Test a simple INSERT to see what fails
    echo "<h3>Registration Test:</h3>";
    echo "<p>Now try submitting the registration form. Any errors will be displayed on the form.</p>";
    echo "<a href='Car_Wash_Registration.php' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Registration Form</a>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection error: " . $e->getMessage() . "</p>";
}
?>