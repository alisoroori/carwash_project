<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/image_upload.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['carwash_id'])) {
        throw new Exception('Unauthorized');
    }

    // Handle image upload if present
    $imageUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageUrl = uploadServiceImage($_FILES['image']);
    }

    $stmt = $conn->prepare("
        INSERT INTO services (
            carwash_id, 
            name, 
            description, 
            price, 
            duration, 
            image_url,
            category_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        'issdisi',
        $_SESSION['carwash_id'],
        $_POST['name'],
        $_POST['description'],
        $_POST['price'],
        $_POST['duration'],
        $imageUrl,
        $_POST['category_id']
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Service added successfully'
        ]);
    } else {
        throw new Exception('Failed to add service');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
