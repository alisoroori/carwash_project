<?php
/**
 * Admin Account Creator for CarWash System
 * Creates a secure admin account in the database
 */

// Include database connection
require_once __DIR__ . '/backend/includes/db.php';

echo "========================================\n";
echo "CarWash Admin Account Creator\n";
echo "========================================\n\n";

// Admin credentials
$admin_email = 'admin@carwash.com';
$admin_password = 'Admin@2025!CarWash';  // Strong password
$admin_name = 'System Administrator';
$admin_phone = '+90 555 123 4567';

try {
    // Get database connection
    $conn = getDBConnection();
    
    // Check if admin already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR role = 'admin'");
    $checkStmt->execute([$admin_email]);
    
    if ($checkStmt->rowCount() > 0) {
        echo "⚠️  Admin account already exists!\n\n";
        
        // Update existing admin password
        $password_hash = password_hash($admin_password, PASSWORD_BCRYPT);
        $updateStmt = $conn->prepare("UPDATE users SET password = ?, full_name = ?, phone = ? WHERE role = 'admin' LIMIT 1");
        $updateStmt->execute([$password_hash, $admin_name, $admin_phone]);
        
        echo "✅ Admin account updated successfully!\n\n";
    } else {
        // Create new admin account
        $password_hash = password_hash($admin_password, PASSWORD_BCRYPT);
        
        $insertStmt = $conn->prepare("
            INSERT INTO users (username, full_name, email, password, phone, role) 
            VALUES (?, ?, ?, ?, ?, 'admin')
        ");
        
        $insertStmt->execute([
            'admin',
            $admin_name,
            $admin_email,
            $password_hash,
            $admin_phone
        ]);
        
        echo "✅ Admin account created successfully!\n\n";
    }
    
    // Display credentials
    echo "========================================\n";
    echo "ADMIN LOGIN CREDENTIALS\n";
    echo "========================================\n\n";
    echo "🔐 Email:    " . $admin_email . "\n";
    echo "🔑 Password: " . $admin_password . "\n";
    echo "👤 Name:     " . $admin_name . "\n";
    echo "📱 Phone:    " . $admin_phone . "\n";
    echo "🔒 Role:     admin\n\n";
    
    echo "========================================\n";
    echo "IMPORTANT SECURITY NOTES:\n";
    echo "========================================\n\n";
    echo "⚠️  Please change the password after first login!\n";
    echo "⚠️  Keep these credentials secure and confidential.\n";
    echo "⚠️  Delete this file after creating the admin account.\n\n";
    
    echo "========================================\n";
    echo "LOGIN URL:\n";
    echo "========================================\n\n";
    echo "🌐 http://localhost/carwash_project/backend/auth/login.php\n\n";
    
    echo "✅ Setup complete! You can now login as admin.\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
