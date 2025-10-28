<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['carwash_id'])) {
        throw new Exception('Unauthorized');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['reviewId']) || !isset($data['reply'])) {
        throw new Exception('Missing required fields');
    }

    // Verify the review belongs to this carwash
    $stmt = $conn->prepare("
        SELECT id 
        FROM reviews 
        WHERE id = ? AND carwash_id = ?
    ");

    $stmt->bind_param('ii', $data['reviewId'], $_SESSION['carwash_id']);
    $stmt->execute();

    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Review not found or unauthorized');
    }

    // Update review with reply
    $stmt = $conn->prepare("
        UPDATE reviews 
        SET reply = ?,
            reply_date = NOW()
        WHERE id = ?
    ");

    $stmt->bind_param('si', $data['reply'], $data['reviewId']);

    if ($stmt->execute()) {
        // Send notification to customer
        $stmt = $conn->prepare("
            SELECT 
                u.email,
                u.name as user_name,
                c.name as carwash_name
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            JOIN carwash c ON r.carwash_id = c.id
            WHERE r.id = ?
        ");

        $stmt->bind_param('i', $data['reviewId']);
        $stmt->execute();
        $notificationData = $stmt->get_result()->fetch_assoc();

        // Send email notification (implement your email sending logic here)
        // sendReviewReplyNotification($notificationData, $data['reply']);

        echo json_encode([
            'success' => true,
            'message' => 'Reply added successfully'
        ]);
    } else {
        throw new Exception('Failed to add reply');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
