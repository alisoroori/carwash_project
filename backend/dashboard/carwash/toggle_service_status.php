<?php
session_start();
// CSRF helper
$csrf_helper = __DIR__ . '/../../includes/csrf_helper.php';
if (file_exists($csrf_helper)) require_once $csrf_helper;
require_once '../../includes/db.php';
// Request helpers: JSON body parsing + structured error responses
if (file_exists(__DIR__ . '/../../includes/request_helpers.php')) {
    require_once __DIR__ . '/../../includes/request_helpers.php';
}

// Set JSON response header
header('Content-Type: application/json');

// API response helpers
if (file_exists(__DIR__ . '/../../includes/api_response.php')) {
    require_once __DIR__ . '/../../includes/api_response.php';
}

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'carwash') {
    api_error('Not authenticated', 403);
}

// Validate input parameters
if (!isset($_POST['service_id']) || !isset($_POST['current_status'])) {
    api_error('Missing required parameters', 400);
}

// CSRF validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf_token']) || !is_string($token) || !function_exists('hash_equals') || !hash_equals((string)$_SESSION['csrf_token'], (string)$token)) {
        api_error('Invalid CSRF token', 403);
    }
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get carwash ID for verification (use canonical `carwashes` table)
    $stmt = $conn->prepare("SELECT id FROM carwashes WHERE owner_id = ?");
    $owner_id = (int)($_SESSION['user_id'] ?? 0);
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $carwash = $stmt->get_result()->fetch_assoc();

    if (!$carwash) {
        throw new Exception('Carwash not found');
    }

    // Sanitize inputs
    $service_id = (int)filter_var($_POST['service_id'], FILTER_SANITIZE_NUMBER_INT);
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

    $payload = ['new_status' => $new_status];
    if (isset($warning)) {
        $payload['warning'] = $warning;
    }
    api_success('Service status updated successfully', $payload);
} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        try { $conn->rollback(); } catch (Throwable $_) { }
    }
    if (function_exists('send_structured_error_response')) {
        send_structured_error_response($e, 500);
    }
    api_error($e->getMessage(), 500);
}
