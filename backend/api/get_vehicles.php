<?php

require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Database;

Auth::requireAuth();

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];

    $vehicles = $db->fetchAll("
        SELECT
            id,
            brand,
            model,
            year,
            color,
            license_plate,
            vehicle_type,
            notes,
            image_path,
            created_at,
            updated_at
        FROM vehicles
        WHERE user_id = :user_id
        ORDER BY created_at DESC
    ", ['user_id' => $userId]);

    // Format image paths
    foreach ($vehicles as &$vehicle) {
        if ($vehicle['image_path']) {
            $vehicle['image_path'] = BASE_URL . '/' . $vehicle['image_path'];
        }
    }

    Response::success('Vehicles retrieved successfully', ['vehicles' => $vehicles]);

} catch (Exception $e) {
    Response::error('Failed to retrieve vehicles: ' . $e->getMessage());
}
?>