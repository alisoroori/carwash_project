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
    
    // Verify reservation is completed (support both English and Turkish)
    if ($reservation['status'] !== 'completed' && $reservation['status'] !== 'Tamamlandı') {
        error_log("Review API: Attempt to review non-completed reservation, id={$reservation_id}, status={$reservation['status']}");
        Response::error('Only completed reservations can be reviewed', 400);
    }
    
    // Check if review already exists
    // Determine reviews table reference column (reservation_id vs booking_id vs order_id)
    $reviewRefColumn = 'reservation_id'; // default expected column
    try {
        $cols = $db->fetchAll('DESCRIBE reviews');
        $colNames = array_map(function($c){ return $c['Field'] ?? $c['field'] ?? ''; }, $cols ?: []);
        if (in_array('reservation_id', $colNames)) {
            $reviewRefColumn = 'reservation_id';
        } elseif (in_array('booking_id', $colNames)) {
            $reviewRefColumn = 'booking_id';
        } elseif (in_array('order_id', $colNames)) {
            $reviewRefColumn = 'order_id';
        } else {
            // fallback: pick first INT-like column after id and user_id
            foreach ($colNames as $cn) {
                if (preg_match('/id$/i', $cn) && $cn !== 'id' && $cn !== 'user_id') {
                    $reviewRefColumn = $cn;
                    break;
                }
            }
        }
    } catch (Throwable $e) {
        // If DESCRIBE fails, log and continue with default
        error_log('Review API: Failed to DESCRIBE reviews table: ' . $e->getMessage());
    }

    // Ensure reviews table columns have expected types (rating numeric, comment text)
    try {
        if (!empty($cols) && is_array($cols)) {
            $colMap = [];
            foreach ($cols as $c) {
                $fname = $c['Field'] ?? $c['field'] ?? '';
                $colMap[$fname] = $c;
            }

            // Ensure rating column is integer-like
            if (isset($colMap['rating'])) {
                $ratingType = strtolower($colMap['rating']['Type'] ?? $colMap['rating']['type'] ?? '');
                if (strpos($ratingType, 'int') === false && strpos($ratingType, 'tinyint') === false) {
                    // Attempt to alter column to tinyint
                    try {
                        $db->query("ALTER TABLE reviews MODIFY COLUMN rating TINYINT(1) UNSIGNED NOT NULL COMMENT 'Rating from 1 to 5 stars'");
                        error_log('Review API: Altered reviews.rating to TINYINT');
                    } catch (Throwable $_e) {
                        error_log('Review API: Failed to alter reviews.rating: ' . $_e->getMessage());
                    }
                }
            }

            // Ensure comment column exists and is text
            if (!isset($colMap['comment'])) {
                try {
                    $db->query("ALTER TABLE reviews ADD COLUMN comment TEXT NULL COMMENT 'Optional review text'");
                    error_log('Review API: Added missing reviews.comment column');
                } catch (Throwable $_e) {
                    error_log('Review API: Failed to add reviews.comment: ' . $_e->getMessage());
                }
            } else {
                $commentType = strtolower($colMap['comment']['Type'] ?? $colMap['comment']['type'] ?? '');
                if (strpos($commentType, 'text') === false && strpos($commentType, 'varchar') === false) {
                    try {
                        $db->query("ALTER TABLE reviews MODIFY COLUMN comment TEXT NULL COMMENT 'Optional review text'");
                        error_log('Review API: Modified reviews.comment to TEXT');
                    } catch (Throwable $_e) {
                        error_log('Review API: Failed to modify reviews.comment: ' . $_e->getMessage());
                    }
                }
            }
        }
    } catch (Throwable $e) {
        error_log('Review API: Schema validation error: ' . $e->getMessage());
    }

    $existingReview = $db->fetchOne(
        "SELECT id FROM reviews WHERE user_id = :user_id AND {$reviewRefColumn} = :ref_id",
        ['user_id' => $user_id, 'ref_id' => $reservation_id]
    );
    
    if ($existingReview) {
        error_log("Review API: Duplicate review attempt, reservation_id={$reservation_id}, user_id={$user_id}");
        Response::error('You have already reviewed this reservation', 400);
    }
    
    // Begin transaction
    $pdo = $db->getPdo();
    $pdo->beginTransaction();
    
    try {
        // Build insert payload using detected reference column
        $insertPayload = [
            'user_id' => $user_id,
            $reviewRefColumn => $reservation_id,
            'carwash_id' => $reservation['carwash_id'],
            'rating' => $rating,
            'comment' => $comment
        ];

        // Validate payload keys against actual table columns to avoid SQL errors
        try {
            $validCols = [];
            $cols = $db->fetchAll('DESCRIBE reviews');
            foreach ($cols as $c) {
                $validCols[] = $c['Field'] ?? $c['field'] ?? '';
            }
            // Filter payload to only include valid columns
            $insertPayload = array_intersect_key($insertPayload, array_flip($validCols));
        } catch (Throwable $_e) {
            // Ignore - will attempt insert and let DB error be caught
        }

        // Insert review
        $reviewInserted = false;
        try {
            $reviewInserted = $db->insert('reviews', $insertPayload);
        } catch (Throwable $ie) {
            error_log('Review API: Insert failed - ' . $ie->getMessage());
            throw new Exception('Failed to insert review: ' . $ie->getMessage());
        }
        
        if (!$reviewInserted) {
            throw new Exception('Failed to insert review');
        }
        
        // Obtain last insert id in a safe way
        try {
            $review_id = $db->lastInsertId();
        } catch (Throwable $_e) {
            $review_id = null;
        }
        
        // Ensure bookings.review_status exists; add if missing
        try {
            $bookingCols = $db->fetchAll('DESCRIBE bookings');
            $bookingColNames = array_map(function($c){ return $c['Field'] ?? $c['field'] ?? ''; }, $bookingCols ?: []);
            if (!in_array('review_status', $bookingColNames)) {
                try {
                    $db->query("ALTER TABLE bookings ADD COLUMN review_status ENUM('pending','reviewed') DEFAULT 'pending' COMMENT 'Whether customer has left a review'");
                    error_log('Review API: Added bookings.review_status column');
                } catch (Throwable $_e) {
                    error_log('Review API: Failed to add bookings.review_status: ' . $_e->getMessage());
                }
            }
        } catch (Throwable $_e) {
            error_log('Review API: Could not DESCRIBE bookings: ' . $_e->getMessage());
        }

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
