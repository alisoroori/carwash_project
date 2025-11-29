<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;

$db = Database::getInstance();

echo "=== CUSTOMER USERS DATA AUDIT ===\n\n";

$users = $db->fetchAll(
    "SELECT u.id, u.full_name, u.username, u.email, u.address AS user_address, up.address AS profile_address
    FROM users u 
    LEFT JOIN user_profiles up ON u.id = up.user_id 
    WHERE u.role = 'customer' 
    ORDER BY u.id"
);

$incomplete = 0;
$complete = 0;

foreach ($users as $user) {
    $hasName = !empty($user['full_name']);
    $hasUsername = !empty($user['username']);
    $hasAddress = !empty($user['profile_address']) || !empty($user['user_address']);
    
    $status = ($hasName && $hasUsername) ? 'OK' : 'INCOMPLETE';
    
    if ($status === 'INCOMPLETE') {
        $incomplete++;
        echo "⚠ User {$user['id']}: {$status}\n";
        if (!$hasName) echo "  - Missing: full_name\n";
        if (!$hasUsername) echo "  - Missing: username\n";
        echo "  - Email: {$user['email']}\n";
    } else {
        $complete++;
        echo "✓ User {$user['id']}: {$user['full_name']} (@{$user['username']})\n";
    }
}

echo "\n" . str_repeat('-', 50) . "\n";
echo "SUMMARY:\n";
echo "  Complete profiles: {$complete}\n";
echo "  Incomplete profiles: {$incomplete}\n";

if ($incomplete > 0) {
    echo "\n⚠ WARNING: {$incomplete} users have missing required data!\n";
    echo "These users will see empty fields in Profile Edit form.\n";
    echo "Recommended: Add username defaults or prompt users to complete profile.\n";
}

echo "\n=== AUDIT COMPLETE ===\n";
