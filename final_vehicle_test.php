<?php
// Comprehensive test for vehicle image loading fix
require_once __DIR__ . '/backend/includes/bootstrap.php';
require_once __DIR__ . '/backend/includes/db.php';
require_once __DIR__ . '/backend/classes/Session.php';

Session::start();

// Simulate login for user 14
$_SESSION['user_id'] = 14;
$_SESSION['user_role'] = 'customer';

echo "=== VEHICLE IMAGE LOADING TEST ===\n\n";

// Test 1: Check database data
echo "1. Database vehicle data for user 14:\n";
$stmt = $pdo->prepare('SELECT id, brand, model, license_plate, image_path FROM user_vehicles WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([14]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($vehicles as $v) {
    echo "  ID: {$v['id']}, Brand: {$v['brand']}, Model: {$v['model']}, Image: {$v['image_path']}\n";
}

echo "\n2. Testing vehicle API list action:\n";
// Simulate API call
$_GET['action'] = 'list';
ob_start();
include __DIR__ . '/backend/dashboard/vehicle_api.php';
$apiOutput = ob_get_clean();

$json = json_decode($apiOutput, true);
if ($json && isset($json['data']['vehicles'])) {
    echo "  API returned " . count($json['data']['vehicles']) . " vehicles\n";
    foreach ($json['data']['vehicles'] as $v) {
        $imagePath = $v['image_path'] ?? 'none';
        echo "    ID: {$v['id']}, Image path: $imagePath\n";

        // Check if image file exists
        if ($imagePath && $imagePath !== '/carwash_project/frontend/assets/default-car.png') {
            $filePath = str_replace('/carwash_project', __DIR__, $imagePath);
            $exists = file_exists($filePath) ? 'EXISTS' : 'MISSING';
            echo "      File check: $exists ($filePath)\n";
        }
    }
} else {
    echo "  API call failed or returned invalid JSON\n";
    echo "  Raw output: " . substr($apiOutput, 0, 200) . "...\n";
}

echo "\n3. Testing image URL resolution:\n";
// Test the resolveVehicleImageUrl function (simplified version)
function resolveVehicleImageUrl($path) {
    if (empty($path)) {
        return '/carwash_project/frontend/assets/default-car.png';
    }

    // If it already starts with /carwash_project/, keep as-is
    if (strpos($path, '/carwash_project/') === 0) {
        return $path;
    }

    // If it starts with /, add /carwash_project prefix
    if (strpos($path, '/') === 0) {
        return '/carwash_project' . $path;
    }

    // Otherwise, assume it's a relative path and add full prefix
    return '/carwash_project/backend/uploads/vehicles/' . $path;
}

foreach ($vehicles as $v) {
    $original = $v['image_path'];
    $resolved = resolveVehicleImageUrl($original);
    echo "  Original: $original → Resolved: $resolved\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>