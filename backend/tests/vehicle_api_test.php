<?php
/**
 * Vehicle API Wiring Test
 *
 * Directly tests the vehicle_api.php endpoints by simulating AJAX calls.
 * Tests create, read, update, delete operations and verifies DB state.
 *
 * Usage:
 *   php backend/tests/vehicle_api_test.php
 *
 * Requires:
 * - Session simulation (we'll use a test user)
 * - Test data insertion/deletion
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/db.php';

function info($s) { echo "[INFO] $s\n"; }
function warn($s) { echo "[WARN] $s\n"; }
function err($s) { echo "[ERROR] $s\n"; }

// Simulate session for a test user
session_start();
$_SESSION['user_id'] = 1; // Assume user ID 1 exists
$_SESSION['role'] = 'customer';

$pdo = getDBConnection();

$baseUrl = 'http://localhost/carwash_project/backend/dashboard/vehicle_api.php';
$uniqueMarker = 'APITEST-' . bin2hex(random_bytes(5));

// Test data
$testData = [
    'car_brand' => 'TestBrand-' . $uniqueMarker,
    'car_model' => 'TestModel',
    'license_plate' => 'TEST-' . rand(1000,9999),
    'car_year' => '2020',
    'car_color' => 'Blue',
    'csrf_token' => 'dummy' // We'll handle CSRF in API
];

// 1. Test CREATE
info("Testing CREATE operation...");
$ch = curl_init($baseUrl);
$postData = http_build_query(array_merge($testData, ['action' => 'create']));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_COOKIE => 'PHPSESSID=' . session_id()
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

info("CREATE response HTTP $httpCode: " . substr($response, 0, 200) . '...');

$json = json_decode($response, true);
if ($json && isset($json['success']) && $json['success']) {
    $vehicleId = $json['vehicle_id'] ?? null;
    info("CREATE successful, vehicle_id: $vehicleId");
} else {
    err("CREATE failed: " . ($json['message'] ?? 'Unknown error'));
    exit(1);
}

// 2. Test READ (list vehicles)
info("Testing READ operation...");
$ch = curl_init($baseUrl . '?action=list');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIE => 'PHPSESSID=' . session_id()
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

info("READ response HTTP $httpCode");
$json = json_decode($response, true);
if ($json && isset($json['success']) && $json['success']) {
    $vehicles = $json['vehicles'] ?? [];
    $found = false;
    foreach ($vehicles as $v) {
        if (strpos($v['car_brand'], $uniqueMarker) !== false) {
            $found = true;
            info("Vehicle found in list: " . json_encode($v));
            break;
        }
    }
    if (!$found) warn("Test vehicle not found in list");
} else {
    err("READ failed: " . ($json['message'] ?? 'Unknown error'));
}

// 3. Test UPDATE
if ($vehicleId) {
    info("Testing UPDATE operation...");
    $updateData = array_merge($testData, [
        'action' => 'update',
        'vehicle_id' => $vehicleId,
        'car_brand' => 'UpdatedBrand-' . $uniqueMarker
    ]);
    $ch = curl_init($baseUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($updateData),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_COOKIE => 'PHPSESSID=' . session_id()
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    info("UPDATE response HTTP $httpCode: " . substr($response, 0, 200) . '...');
    $json = json_decode($response, true);
    if ($json && isset($json['success']) && $json['success']) {
        info("UPDATE successful");
    } else {
        err("UPDATE failed: " . ($json['message'] ?? 'Unknown error'));
    }
}

// 4. Test DELETE
if ($vehicleId) {
    info("Testing DELETE operation...");
    $deleteData = [
        'action' => 'delete',
        'vehicle_id' => $vehicleId,
        'csrf_token' => 'dummy'
    ];
    $ch = curl_init($baseUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($deleteData),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_COOKIE => 'PHPSESSID=' . session_id()
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    info("DELETE response HTTP $httpCode: " . substr($response, 0, 200) . '...');
    $json = json_decode($response, true);
    if ($json && isset($json['success']) && $json['success']) {
        info("DELETE successful");
    } else {
        err("DELETE failed: " . ($json['message'] ?? 'Unknown error'));
    }

    // Verify deletion in DB
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_vehicles WHERE id = ? AND user_id = ?");
    $stmt->execute([$vehicleId, $_SESSION['user_id']]);
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        info("DB verification: Vehicle deleted successfully");
    } else {
        warn("DB verification: Vehicle still exists in DB");
    }
}

info("Test completed.");
?>