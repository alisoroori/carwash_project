<?php

require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Response;
use App\Classes\Database;
use App\Classes\Validator;

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
    $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE id = :id AND user_id = :user_id", [
        'id' => $vehicleId,
        'user_id' => $userId
    ]);

    if (!$vehicle) {
        Response::error('Vehicle not found');
        exit;
    }

    // Validate input
    $validator = new Validator($_POST);
    $validator->required(['brand', 'model', 'year', 'license_plate', 'vehicle_type']);
    $validator->numeric(['year']);
    $validator->minLength(['brand' => 1, 'model' => 1, 'license_plate' => 1]);
    $validator->maxLength(['brand' => 50, 'model' => 50, 'license_plate' => 20, 'color' => 30, 'notes' => 500]);

    if (!$validator->isValid()) {
        Response::error('Validation failed', $validator->getErrors());
        exit;
    }

    // Check for duplicate license plate (excluding current vehicle)
    $existing = $db->fetchOne("SELECT id FROM vehicles WHERE license_plate = :plate AND user_id = :user_id AND id != :id", [
        'plate' => $_POST['license_plate'],
        'user_id' => $userId,
        'id' => $vehicleId
    ]);

    if ($existing) {
        Response::error('A vehicle with this license plate already exists');
        exit;
    }

    $imagePath = $vehicle['image_path'];

    // Handle image upload/update
    if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] === UPLOAD_ERR_OK) {
        // Delete old image if exists
        if ($vehicle['image_path']) {
            $oldImagePath = __DIR__ . '/../../' . $vehicle['image_path'];
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        $uploadDir = __DIR__ . '/../../uploads/vehicles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = uniqid('vehicle_') . '_' . time() . '.' . pathinfo($_FILES['vehicle_image']['name'], PATHINFO_EXTENSION);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $targetPath)) {
            $imagePath = 'uploads/vehicles/' . $fileName;
        } else {
            Response::error('Failed to upload image');
            exit;
        }
    }

    // Update vehicle
    $updateData = [
        'brand' => $_POST['brand'],
        'model' => $_POST['model'],
        'year' => (int)$_POST['year'],
        'color' => $_POST['color'] ?? null,
        'license_plate' => $_POST['license_plate'],
        'vehicle_type' => $_POST['vehicle_type'],
        'notes' => $_POST['notes'] ?? null,
        'image_path' => $imagePath,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $updated = $db->update('vehicles', $updateData, 'id = :id', ['id' => $vehicleId]);

    if ($updated) {
        $updatedVehicle = $db->fetchOne("SELECT * FROM vehicles WHERE id = :id", ['id' => $vehicleId]);
        if ($updatedVehicle['image_path']) {
            $updatedVehicle['image_path'] = BASE_URL . '/' . $updatedVehicle['image_path'];
        }
        Response::success('Vehicle updated successfully', ['vehicle' => $updatedVehicle]);
    } else {
        Response::error('Failed to update vehicle');
    }

} catch (Exception $e) {
    Response::error('Failed to update vehicle: ' . $e->getMessage());
}
?>