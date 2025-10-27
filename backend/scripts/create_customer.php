<?php
// CLI helper: create a customer user in the database.
// Usage (PowerShell):
// php .\backend\scripts\create_customer.php --email=customer@example.com --password=password123

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Database;

// Simple arg parser
$email = 'customer@example.com';
$password = 'password123';
foreach ($argv as $arg) {
    if (strpos($arg, '--email=') === 0) {
        $email = substr($arg, strlen('--email='));
    }
    if (strpos($arg, '--password=') === 0) {
        $password = substr($arg, strlen('--password='));
    }
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email: $email\n";
    exit(1);
}

try {
    $db = Database::getInstance();

    // Check if user exists
    $existing = $db->fetchOne('SELECT id, email FROM users WHERE LOWER(email) = :email LIMIT 1', ['email' => strtolower($email)]);

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    if ($existing) {
        // Update existing user: set role to customer and update password
        $updated = $db->update('users', ['password' => $hashed, 'role' => 'customer', 'is_active' => 1], ['id' => $existing['id']]);
        if ($updated) {
            echo "Updated existing user (id={$existing['id']}) to customer and set password.\n";
            echo "Email: $email\n";
            echo "Password: $password\n";
            exit(0);
        } else {
            echo "Failed to update existing user.\n";
            exit(2);
        }
    }

    // Insert new user
    $userId = $db->insert('users', [
        'username' => strtolower(explode('@', $email)[0]),
        'full_name' => 'Test Customer',
        'email' => $email,
        'password' => $hashed,
        'role' => 'customer',
        'is_active' => 1
    ]);

    if ($userId) {
        echo "Created customer user with id=$userId\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
        exit(0);
    }

    echo "Failed to create user.\n";
    exit(3);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(4);
}
