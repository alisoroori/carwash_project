<?php
/**
 * Debug login script
 */
require_once __DIR__ . '/vendor/autoload.php';

use App\Classes\Database;

try {
    $db = Database::getInstance();
    
    // Get user
    $user = $db->fetchOne('SELECT id, email, password, role, status FROM users WHERE id = 1');
    
    echo "User record:\n";
    echo "  ID: " . $user['id'] . "\n";
    echo "  Email: " . $user['email'] . "\n";
    echo "  Password hash length: " . strlen($user['password']) . "\n";
    echo "  Password hash starts with: " . substr($user['password'], 0, 7) . "\n";
    echo "  Role: " . $user['role'] . "\n";
    echo "  Status: " . $user['status'] . "\n";
    
    // Test password verification
    $testPassword = 'password';
    $verified = password_verify($testPassword, $user['password']);
    echo "\nPassword 'password' verification: " . ($verified ? "✓ PASS" : "✗ FAIL") . "\n";
    
    if (!$verified) {
        echo "\nGenerating new hash...\n";
        $newHash = password_hash('password', PASSWORD_DEFAULT);
        echo "New hash: $newHash\n";
        echo "New hash length: " . strlen($newHash) . "\n";
        
        // Update it
        $db->update('users', ['password' => $newHash], ['id' => 1]);
        echo "\n✓ Password updated!\n";
        
        // Verify again
        $user2 = $db->fetchOne('SELECT password FROM users WHERE id = 1');
        $verified2 = password_verify('password', $user2['password']);
        echo "Re-verification: " . ($verified2 ? "✓ PASS" : "✗ FAIL") . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
