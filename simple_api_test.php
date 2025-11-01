<?php
// Simple test for vehicle API response
require_once 'backend/includes/db.php';

// Simulate the API logic for list action
$user_id = 14;

$stmt = $pdo->prepare('SELECT id, brand, model, license_plate, year, color, image_path, created_at FROM user_vehicles WHERE user_id = :uid ORDER BY created_at DESC');
$stmt->execute([':uid' => $user_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process image paths to ensure they are valid public URLs
foreach ($rows as &$row) {
    $imagePath = $row['image_path'];

    if (empty($imagePath)) {
        // Empty path - use default image
    $row['image_path'] = '/carwash_project/frontend/assets/images/default-car.png';
    } elseif (strpos($imagePath, '/carwash_project/') !== 0) {
        // Relative path without full prefix - add it
        if (strpos($imagePath, '/') === 0) {
            $row['image_path'] = '/carwash_project' . $imagePath;
        } else {
            // Invalid path - use default
            $row['image_path'] = '/carwash_project/frontend/assets/images/default-car.png';
        }
    }
    // If it already starts with /carwash_project/, keep as-is
}

echo json_encode(['status' => 'success', 'message' => 'Vehicles listed', 'data' => ['vehicles' => $rows]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
?>