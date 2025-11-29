<?php

require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Database;

Auth::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed');
    exit;
}

try {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];

    // Validate vehicle ID
    if (!isset($_POST['vehicle_id']) || !is_numeric($_POST['vehicle_id'])) {
        Response::error('Invalid vehicle ID');
        exit;
    }

    $vehicleId = (int)$_POST['vehicle_id'];

    // Check if vehicle exists and belongs to user
    $vehicle = $db->fetchOne("SELECT * FROM user_vehicles WHERE id = :id AND user_id = :user_id", [
        'id' => $vehicleId,
        'user_id' => $userId
    ]);

    if (!$vehicle) {
        Response::error('Vehicle not found');
        exit;
    }

    // Check if vehicle has active bookings (using license plate since bookings don't have vehicle_id)
    $activeBookings = $db->fetchOne("SELECT COUNT(*) as count FROM bookings WHERE vehicle_plate = :license_plate AND status IN ('pending', 'confirmed', 'in_progress')", [
        'license_plate' => $vehicle['license_plate']
    ]);

    if ($activeBookings['count'] > 0) {
        Response::error('Cannot delete vehicle with active bookings');
        exit;
    }

    // Delete image file if exists
    if ($vehicle['image_path']) {
        $imagePath = __DIR__ . '/../../' . $vehicle['image_path'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    // Delete vehicle
    $deleted = $db->delete('user_vehicles', ['id' => $vehicleId, 'user_id' => $userId]);

    if ($deleted) {
        Response::success('Vehicle deleted successfully');
    } else {
        Response::error('Failed to delete vehicle');
    }

} catch (Exception $e) {
    Response::error('Failed to delete vehicle: ' . $e->getMessage());
}
?>