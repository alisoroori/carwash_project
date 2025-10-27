<?php
// Test runner: create a session by logging in programmatically and include customer pages to detect runtime errors
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Auth;
use App\Classes\Session;

// Test credentials - must match the created customer
$email = $argv[1] ?? 'customer@example.com';
$password = $argv[2] ?? 'password123';

// Start session
Session::start();

$auth = new Auth();
$result = $auth->login($email, $password);

echo "Login result: ";
var_export($result);
echo "\n\n";

if (empty($result['success'])) {
    echo "Login failed - cannot proceed to page includes.\n";
    exit(1);
}

$pages = [
    __DIR__ . '/../dashboard/customer/index.php',
    __DIR__ . '/../dashboard/customer/my_bookings.php',
    __DIR__ . '/../dashboard/customer/process_booking.php',
    __DIR__ . '/../dashboard/customer/test_db.php',
];

foreach ($pages as $page) {
    echo "\n--- Including: $page ---\n";
    // Capture output and errors
    ob_start();
    try {
        include $page;
    } catch (Throwable $e) {
        echo "Exception while including $page: " . $e->getMessage() . "\n";
    }
    $out = ob_get_clean();
    // Truncate large HTML for readability
    $snippet = substr($out, 0, 8000);
    echo $snippet;
    if (strlen($out) > 8000) echo "\n... (output truncated) ...\n";
    echo "\n--- End: $page ---\n";
}

echo "\nAll pages included.\n";
