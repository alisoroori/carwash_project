<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/review_manager.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['carwash_id']) || !isset($data['rating'])) {
        throw new Exception('Missing required fields');
    }

    $reviewManager = new ReviewManager($conn);

    // Check if user can review
    if (!$reviewManager->canUserReview($_SESSION['user_id'], $data['carwash_id'])) {
        throw new Exception('You can only review once every 30 days');
    }

    // Add review
    if ($reviewManager->addReview(
        $_SESSION['user_id'],
        $data['carwash_id'],
        $data['order_id'] ?? null,
        $data['rating'],
        $data['comment'] ?? ''
    )) {
        echo json_encode([
            'success' => true,
            'message' => 'Review submitted successfully'
        ]);
    } else {
        throw new Exception('Failed to submit review');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
