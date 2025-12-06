<?php
/**
 * Fix admin password for existing carwash_db
 */
require_once __DIR__ . '/vendor/autoload.php';

use App\Classes\Database;

try {
    $db = Database::getInstance();
    
    // Check existing admins
    $admins = $db->fetchAll('SELECT id, email, role FROM users WHERE role = ?', ['admin']);
    echo "Existing admin users:\n";
    foreach ($admins as $admin) {
        echo "  ID: {$admin['id']}, Email: {$admin['email']}\n";
    }
    
    // Generate a proper bcrypt hash for 'password'
    $hash = password_hash('password', PASSWORD_DEFAULT);
    
    // Update admin with id=1
    $db->update('users', ['password' => $hash], ['id' => 1]);
    
    echo "\n✓ Password updated for user ID 1!\n";
    echo "Email: smoke+test@example.test\n";
    echo "Password: password\n";
    
    // Also update kral@gmail.com (id=28)
    $db->update('users', ['password' => $hash], ['id' => 28]);
    echo "\n✓ Password also updated for user ID 28!\n";
    echo "Email: kral@gmail.com\n";
    echo "Password: password\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
