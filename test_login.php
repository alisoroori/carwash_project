<?php
/**
 * Quick login test - simulates password verification only
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;

echo "Testing password verification for kral@gmail.com...\n";

$db = Database::getInstance();
$user = $db->fetchOne("SELECT id, email, password, is_active FROM users WHERE email = :email", ['email' => 'kral@gmail.com']);

if ($user) {
    echo "User found: ID={$user['id']}, email={$user['email']}, is_active={$user['is_active']}\n";
    $verified = password_verify('password', $user['password']);
    echo "Password verification: " . ($verified ? "SUCCESS" : "FAILED") . "\n";
} else {
    echo "User not found!\n";
}
