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

// Validate input
if (!isset($_POST['booking_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$booking_id = filter_var($_POST['booking_id'], FILTER_SANITIZE_NUMBER_INT);
$new_status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

// Validate status
$valid_statuses = ['confirmed', 'completed', 'cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Verify booking belongs to this carwash
    $stmt = $conn->prepare("
        SELECT b.*, c.owner_id 
        FROM bookings b
        JOIN carwashes c ON b.carwash_id = c.id
        WHERE b.id = ? AND c.owner_id = ?
    ");
    $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Booking not found or unauthorized');
    }

    $booking = $result->fetch_assoc();

    // Check if status change is valid
    $current_status = $booking['status'];
    if (!isValidStatusTransition($current_status, $new_status)) {
        throw new Exception('Invalid status transition');
    }

    // Update booking status
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET status = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("si", $new_status, $booking_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update booking status');
    }

    // If marking as completed, check if we should send review request
    if ($new_status === 'completed') {
        // TODO: Implement review request notification
        // sendReviewRequest($booking['customer_id'], $booking_id);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Booking status updated successfully'
    ]);
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Validate if the status transition is allowed
 */
function isValidStatusTransition($current, $new)
{
    $allowed_transitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'completed' => [], // No further transitions allowed
        'cancelled' => []  // No further transitions allowed
    ];

    return isset($allowed_transitions[$current]) &&
        in_array($new, $allowed_transitions[$current]);
}
?>
<script>
    function updateBookingStatus(id, newStatus) {
        fetch('update_booking_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `booking_id=${id}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    location.reload();
                } else {
                    alert(data.error);
                }
            });
    }
</script>
