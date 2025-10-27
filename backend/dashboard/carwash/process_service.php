<?php
session_start();
require_once '../../includes/db.php';

// Set JSON response header
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'carwash') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Get carwash ID
$stmt = $conn->prepare("SELECT id FROM carwash_profiles WHERE owner_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$carwash = $stmt->get_result()->fetch_assoc();

if (!$carwash) {
    echo json_encode(['success' => false, 'error' => 'Carwash not found']);
    exit();
}

// Validate required fields
$required_fields = ['service_name', 'duration', 'price'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'error' => 'All required fields must be filled']);
        exit();
    }
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Sanitize inputs
    $service_name = filter_var($_POST['service_name'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $duration = filter_var($_POST['duration'], FILTER_SANITIZE_NUMBER_INT);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // Validate inputs
    if ($duration < 1) {
        throw new Exception('Duration must be at least 1 minute');
    }
    if ($price < 0) {
        throw new Exception('Price cannot be negative');
    }

    // Check if updating existing service or creating new one
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update existing service
        $stmt = $conn->prepare("
            UPDATE services 
            SET service_name = ?, 
                description = ?, 
                duration = ?, 
                price = ?
            WHERE id = ? AND carwash_id = ?
        ");
        $stmt->bind_param(
            "ssiidi",
            $service_name,
            $description,
            $duration,
            $price,
            $_POST['id'],
            $carwash['id']
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to update service');
        }

        $message = 'Service updated successfully';
    } else {
        // Create new service
        $stmt = $conn->prepare("
            INSERT INTO services (
                carwash_id, 
                service_name, 
                description, 
                duration, 
                price, 
                status
            ) VALUES (?, ?, ?, ?, ?, 'active')
        ");
        $stmt->bind_param(
            "issid",
            $carwash['id'],
            $service_name,
            $description,
            $duration,
            $price
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to create service');
        }

        $message = 'Service created successfully';
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
