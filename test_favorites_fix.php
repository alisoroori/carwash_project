<?php
/**
 * Test script to verify favorites.php is working correctly
 * This simulates the AJAX request from Customer_Dashboard.php
 */

// Start session
session_start();

// Simulate authenticated user
$_SESSION['user_id'] = 14; // Change this to your test user ID
$_SESSION['role'] = 'customer';
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo "=== Favorites API Test ===\n\n";
echo "Session user_id: " . $_SESSION['user_id'] . "\n";
echo "CSRF token: " . substr($_SESSION['csrf_token'], 0, 16) . "...\n\n";

// Test 1: Add favorite
echo "Test 1: Adding favorite (carwash_id=1)...\n";
$_POST = [
    'carwash_id' => '1',
    'action' => 'add',
    'csrf_token' => $_SESSION['csrf_token']
];
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
include __DIR__ . '/backend/api/favorites.php';
$response1 = ob_get_clean();

echo "Response: " . $response1 . "\n";
$result1 = json_decode($response1, true);
echo "Success: " . ($result1['success'] ? 'YES' : 'NO') . "\n";
echo "Is Favorite: " . ($result1['is_favorite'] ? 'YES' : 'NO') . "\n\n";

// Test 2: Check favorite status (GET request)
echo "Test 2: Checking favorite status...\n";
$_POST = [];
$_GET = ['carwash_id' => '1'];
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
include __DIR__ . '/backend/api/favorites.php';
$response2 = ob_get_clean();

echo "Response: " . $response2 . "\n";
$result2 = json_decode($response2, true);
echo "Success: " . ($result2['success'] ? 'YES' : 'NO') . "\n";
echo "Is Favorite: " . ($result2['is_favorite'] ? 'YES' : 'NO') . "\n\n";

// Test 3: Remove favorite
echo "Test 3: Removing favorite...\n";
$_POST = [
    'carwash_id' => '1',
    'action' => 'remove',
    'csrf_token' => $_SESSION['csrf_token']
];
$_GET = [];
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
include __DIR__ . '/backend/api/favorites.php';
$response3 = ob_get_clean();

echo "Response: " . $response3 . "\n";
$result3 = json_decode($response3, true);
echo "Success: " . ($result3['success'] ? 'YES' : 'NO') . "\n";
echo "Is Favorite: " . ($result3['is_favorite'] ? 'YES' : 'NO') . "\n\n";

// Test 4: Verify CSRF protection
echo "Test 4: Testing CSRF protection (should fail)...\n";
$_POST = [
    'carwash_id' => '1',
    'action' => 'add',
    'csrf_token' => 'invalid_token'
];
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
include __DIR__ . '/backend/api/favorites.php';
$response4 = ob_get_clean();

echo "Response: " . $response4 . "\n";
$result4 = json_decode($response4, true);
echo "Success: " . ($result4['success'] ? 'YES' : 'NO') . "\n";
echo "Expected: NO (CSRF should block)\n\n";

echo "=== All Tests Complete ===\n";
?>
