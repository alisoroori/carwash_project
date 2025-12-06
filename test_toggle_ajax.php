<?php
// Test: Simulate toggle AJAX POST
session_start();

// Set user_id to test (user 27 owns carwash 7)
$_SESSION['user_id'] = 27;

echo "=== Testing Toggle AJAX ===\n";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n\n";

// Check current DB status
require_once __DIR__ . '/backend/includes/bootstrap.php';
use App\Classes\Database;

$db = Database::getInstance();
$before = $db->fetchOne('SELECT id, status, COALESCE(is_active,0) as is_active FROM carwashes WHERE user_id = :uid', ['uid' => 27]);
echo "BEFORE: status='{$before['status']}' is_active={$before['is_active']}\n\n";

// Simulate POST to toggle to Kapalı
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['ajax_workplace_status'] = 'Kapalı';
$_POST['ajax_is_active'] = '0';

echo "Simulating POST: ajax_workplace_status='Kapalı', ajax_is_active='0'\n";

// Include seller_header which should process the POST and exit with JSON
ob_start();
try {
    include __DIR__ . '/backend/includes/seller_header.php';
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();

// Check if JSON response
if (strpos($output, '{') === 0) {
    echo "Response: " . $output . "\n\n";
} else {
    echo "Non-JSON response (first 500 chars):\n" . substr($output, 0, 500) . "\n\n";
}

// Check DB after
$after = $db->fetchOne('SELECT id, status, COALESCE(is_active,0) as is_active FROM carwashes WHERE user_id = :uid', ['uid' => 27]);
echo "AFTER: status='{$after['status']}' is_active={$after['is_active']}\n";

if ($after['status'] === 'Kapalı' && (int)$after['is_active'] === 0) {
    echo "\n✅ SUCCESS: Database updated correctly!\n";
} else {
    echo "\n❌ FAILED: Database was NOT updated!\n";
}
