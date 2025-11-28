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

    // Check for duplicate license plate
    $existing = $db->fetchOne("SELECT id FROM vehicles WHERE license_plate = :plate AND user_id = :user_id", [
        'plate' => $_POST['license_plate'],
        'user_id' => $userId
    ]);

    if ($existing) {
        Response::error('A vehicle with this license plate already exists');
        exit;
    }

    $imagePath = null;

    // Handle image upload
    if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] === UPLOAD_ERR_OK) {
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

    // Insert vehicle
    $vehicleId = $db->insert('vehicles', [
        'user_id' => $userId,
        'brand' => $_POST['brand'],
        'model' => $_POST['model'],
        'year' => (int)$_POST['year'],
        'color' => $_POST['color'] ?? null,
        'license_plate' => $_POST['license_plate'],
        'vehicle_type' => $_POST['vehicle_type'],
        'notes' => $_POST['notes'] ?? null,
        'image_path' => $imagePath,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    if ($vehicleId) {
        $vehicle = $db->fetchOne("SELECT * FROM vehicles WHERE id = :id", ['id' => $vehicleId]);
        if ($vehicle['image_path']) {
            $vehicle['image_path'] = BASE_URL . '/' . $vehicle['image_path'];
        }
        Response::success('Vehicle added successfully', ['vehicle' => $vehicle]);
    } else {
        Response::error('Failed to add vehicle');
    }

} catch (Exception $e) {
    Response::error('Failed to add vehicle: ' . $e->getMessage());
}
?>