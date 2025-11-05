<?php
// backend/api/reviews/submit_review.php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// Bootstrap app (autoload or bootstrap)
$booted = false;
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    $booted = true;
} elseif (file_exists(__DIR__ . '/../../includes/bootstrap.php')) {
    require_once __DIR__ . '/../../includes/bootstrap.php';
    $booted = true;
}

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/review_manager.php';

// Helper to return JSON
function json_exit(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ✅ Allow POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_exit(['success' => false, 'message' => 'Method Not Allowed'], 405);
}

// ✅ Require login
if (empty($_SESSION['user_id'])) {
    json_exit(['success' => false, 'message' => 'Unauthorized'], 401);
}

// ✅ Parse JSON input
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    json_exit(['success' => false, 'message' => 'Invalid JSON body'], 400);
}

// ✅ Validate CSRF token if exists
$csrf_ok = false;
$csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (isset($_SESSION['csrf_token'])) {
    if ($csrf_token && hash_equals($_SESSION['csrf_token'], $csrf_token)) $csrf_ok = true;
}
if (isset($_SESSION['csrf_token']) && !$csrf_ok) {
    json_exit(['success' => false, 'message' => 'Invalid CSRF token'], 401);
}

// ✅ Validate required fields
$carwash_id = (int)($data['carwash_id'] ?? 0);
$rating     = (int)($data['rating'] ?? 0);
$comment    = trim((string)($data['comment'] ?? ''));
$order_id   = isset($data['order_id']) ? (int)$data['order_id'] : null;

if ($carwash_id <= 0 || $rating < 1 || $rating > 5) {
    json_exit(['success' => false, 'message' => 'Validation failed: invalid carwash_id or rating'], 400);
}

try {
    $reviewManager = new ReviewManager($conn);

    // ✅ Check cooldown
    if (!$reviewManager->canUserReview($_SESSION['user_id'], $carwash_id)) {
        json_exit(['success' => false, 'message' => 'You can only review once every 30 days'], 400);
    }

    // ✅ Insert review
    if ($reviewManager->addReview($_SESSION['user_id'], $carwash_id, $order_id, $rating, $comment)) {
        json_exit(['success' => true, 'message' => 'Review submitted successfully'], 200);
    } else {
        json_exit(['success' => false, 'message' => 'Failed to submit review'], 500);
    }

} catch (Throwable $e) {
    error_log("SubmitReview error: " . $e->getMessage());
    json_exit(['success' => false, 'message' => 'Server error'], 500);
}
