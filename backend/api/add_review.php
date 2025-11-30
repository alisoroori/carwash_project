<?php
/**
 * Add Review API
 * Handles customer review submissions for completed reservations
 * 
 * POST Parameters:
 * - reservation_id: ID of the completed reservation
 * - rating: Star rating (1-5)
 * - comment: Review text (optional)
 * - csrf_token: CSRF protection token
 */

require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Database;
use App\Classes\Response;

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/review_api.log');
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Require customer authentication
Auth::requireAuth();

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();
$user_id = $_SESSION['user_id'] ?? null;

// Validate user authentication
if (!$user_id) {
    error_log("Review API: User not authenticated");
    Response::error('User not authenticated', 401);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

try {
    // CSRF validation
    $csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';
    
    if (empty($csrf_token) || empty($session_token) || !hash_equals($session_token, $csrf_token)) {
        error_log("Review API: CSRF validation failed for user_id={$user_id}");
        Response::error('Invalid CSRF token', 403);
    }
    
    // Validate required parameters
    if (!isset($_POST['reservation_id']) || !isset($_POST['rating'])) {
        error_log("Review API: Missing required parameters for user_id={$user_id}");
        Response::error('Missing required parameters: reservation_id and rating are required', 400);
    }
    
    $reservation_id = filter_var($_POST['reservation_id'], FILTER_VALIDATE_INT);
    $rating = filter_var($_POST['rating'], FILTER_VALIDATE_INT);
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    // Validate reservation_id
    if ($reservation_id === false || $reservation_id <= 0) {
        error_log("Review API: Invalid reservation_id for user_id={$user_id}");
        Response::error('Invalid reservation ID', 400);
    }
    
    // Validate rating (must be between 1 and 5)
    if ($rating === false || $rating < 1 || $rating > 5) {
        error_log("Review API: Invalid rating={$rating} for user_id={$user_id}");
        Response::error('Rating must be between 1 and 5 stars', 400);
    }
    
    // Sanitize comment (max 1000 characters)
    if (!empty($comment)) {
        $comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');
        if (strlen($comment) > 1000) {
            $comment = substr($comment, 0, 1000);
        }
    }
    
    // Verify the reservation exists and belongs to the user
    $reservation = $db->fetchOne(
        "SELECT id, user_id, carwash_id, status FROM bookings WHERE id = :id",
        ['id' => $reservation_id]
    );
    
    if (!$reservation) {
        error_log("Review API: Reservation not found, id={$reservation_id}, user_id={$user_id}");
        Response::error('Reservation not found', 404);
    }
    
    // Verify ownership
    if ($reservation['user_id'] != $user_id) {
        error_log("Review API: Unauthorized access attempt, reservation_id={$reservation_id}, user_id={$user_id}, owner={$reservation['user_id']}");
        Response::error('You do not have permission to review this reservation', 403);
    }
    
    // Verify reservation is completed
    if ($reservation['status'] !== 'completed') {
        error_log("Review API: Attempt to review non-completed reservation, id={$reservation_id}, status={$reservation['status']}");
        Response::error('Only completed reservations can be reviewed', 400);
    }
    
    // Check if review already exists
    $existingReview = $db->fetchOne(
        "SELECT id FROM reviews WHERE user_id = :user_id AND booking_id = :booking_id",
        ['user_id' => $user_id, 'booking_id' => $reservation_id]
    );
    
    if ($existingReview) {
        error_log("Review API: Duplicate review attempt, reservation_id={$reservation_id}, user_id={$user_id}");
        Response::error('You have already reviewed this reservation', 400);
    }
    
    // Begin transaction
    $pdo = $db->getPdo();
    $pdo->beginTransaction();
    
    try {
        // Insert review (using booking_id column name from actual table structure)
        $reviewInserted = $db->insert('reviews', [
            'user_id' => $user_id,
            'booking_id' => $reservation_id,
            'carwash_id' => $reservation['carwash_id'],
            'rating' => $rating,
            'comment' => $comment
        ]);
        
        if (!$reviewInserted) {
            throw new Exception('Failed to insert review');
        }
        
        $review_id = $db->lastInsertId();
        
        // Update reservation review_status
        $statusUpdated = $db->update(
            'bookings',
            ['review_status' => 'reviewed'],
            ['id' => $reservation_id]
        );
        
        if ($statusUpdated === false) {
            throw new Exception('Failed to update reservation review status');
        }
        
        // Commit transaction
        $pdo->commit();
        
        error_log("Review API: Successfully added review, review_id={$review_id}, reservation_id={$reservation_id}, user_id={$user_id}, rating={$rating}");
        
        // Return success response
        Response::success('Review submitted successfully', [
            'review_id' => $review_id,
            'reservation_id' => $reservation_id,
            'rating' => $rating,
            'message' => 'Thank you for your feedback!'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Review API Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    error_log('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
    
    Response::error('Failed to submit review: ' . $e->getMessage(), 500);
}
?>
