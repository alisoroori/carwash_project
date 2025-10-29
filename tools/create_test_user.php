<?php
/**
 * CLI helper: create a test customer user for automated tests.
 * Usage: php tools/create_test_user.php
 * Writes credentials to tools/tests/puppeteer/test_user.json
 */
require_once __DIR__ . '/../vendor/autoload.php';

use App\Classes\Database;

// Safe defaults
$email = 'test_customer+ci@example.com';
$password = 'TestPass123!';
$fullName = 'CI Test Customer';

try {
    if (!class_exists('\App\\Classes\\Database')) {
        throw new RuntimeException('Database class not found. Run composer install or ensure autoload is available.');
    }

    $db = Database::getInstance();

    // Check if user exists
    $exists = $db->fetchOne('SELECT id FROM users WHERE email = :email', ['email' => $email]);
    if ($exists) {
        $userId = (int)$exists['id'];
        echo "User already exists with id={$userId}\n";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $userId = $db->insert('users', [
            'username' => $email,
            'full_name' => $fullName,
            'email' => $email,
            'password' => $hashed,
            'role' => 'customer',
            'is_active' => 1
        ]);
        if ($userId === false) throw new RuntimeException('Failed to insert user');
        echo "Created user id={$userId}\n";
    }

    // Ensure output directory exists
    $outDir = __DIR__ . '/tests/puppeteer';
    if (!is_dir($outDir)) @mkdir($outDir, 0755, true);

    $creds = ['email' => $email, 'password' => $password, 'user_id' => $userId];
    file_put_contents($outDir . '/test_user.json', json_encode($creds));
    echo "Wrote credentials to tools/tests/puppeteer/test_user.json\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(2);
}
