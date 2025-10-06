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

// Validate input parameters
if (!isset($_POST['service_id']) || !isset($_POST['current_status'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get carwash ID for verification
    $stmt = $conn->prepare("SELECT id FROM carwashes WHERE owner_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $carwash = $stmt->get_result()->fetch_assoc();

    if (!$carwash) {
        throw new Exception('Carwash not found');
    }

    // Sanitize inputs
    $service_id = filter_var($_POST['service_id'], FILTER_SANITIZE_NUMBER_INT);
    $current_status = $_POST['current_status'] === 'active' ? 'active' : 'inactive';
    $new_status = $current_status === 'active' ? 'inactive' : 'active';

    // Verify service belongs to this carwash and update status
    $stmt = $conn->prepare("
        UPDATE services 
        SET status = ?,
            updated_at = NOW()
        WHERE id = ? AND carwash_id = ?
    ");
    $stmt->bind_param("sii", $new_status, $service_id, $carwash['id']);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update service status');
    }

    // Check if any rows were affected
    if ($stmt->affected_rows === 0) {
        throw new Exception('Service not found or unauthorized');
    }

    // If deactivating, check for future bookings
    if ($new_status === 'inactive') {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as booking_count
            FROM bookings 
            WHERE service_id = ? 
            AND status IN ('pending', 'confirmed')
            AND booking_date >= CURDATE()
        ");
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['booking_count'] > 0) {
            // Optional: You could either prevent deactivation or notify about existing bookings
            $warning = "Note: There are {$result['booking_count']} future bookings for this service.";
        }
    }

    // Commit transaction
    $conn->commit();

    $response = [
        'success' => true,
        'message' => 'Service status updated successfully',
        'new_status' => $new_status
    ];

    if (isset($warning)) {
        $response['warning'] = $warning;
    }

    echo json_encode($response);
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
