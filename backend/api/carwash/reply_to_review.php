<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['carwash_id'])) {
        throw new Exception('Unauthorized');
    }

    // Merge JSON body into $_POST so tokens sent in JSON are validated
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data)) foreach ($data as $k => $v) if (!isset($_POST[$k])) $_POST[$k] = $v;

    // CSRF protection: prefer centralized helper; keep inline fallback during rollout
    $csrf_helper = __DIR__ . '/../../includes/csrf_protect.php';
    if (file_exists($csrf_helper)) {
        require_once $csrf_helper;
        if (function_exists('require_valid_csrf')) {
            // require_valid_csrf() will emit 403 JSON and exit if invalid
            require_valid_csrf();
        }
    } else {
        $csrfToken = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (empty($_SESSION['csrf_token']) || empty($csrfToken) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$csrfToken)) {
            error_log('CSRF: missing or invalid token in carwash/reply_to_review.php');
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }
    }

    if (!isset($data['reviewId']) || !isset($data['reply'])) {
        throw new Exception('Missing required fields');
    }

    // Verify the review belongs to this carwash
    $stmt = $conn->prepare("\n        SELECT id \n        FROM reviews \n        WHERE id = ? AND carwash_id = ?\n    ");

    $stmt->bind_param('ii', $data['reviewId'], $_SESSION['carwash_id']);
    $stmt->execute();

    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Review not found or unauthorized');
    }

    // Update review with reply
    $stmt = $conn->prepare("\n        UPDATE reviews \n        SET reply = ?,\n            reply_date = NOW()\n        WHERE id = ?\n    ");

    $stmt->bind_param('si', $data['reply'], $data['reviewId']);

    if ($stmt->execute()) {
        // Send notification to customer
        $stmt = $conn->prepare("\n            SELECT \n                u.email,\n                u.name as user_name,\n                c.name as carwash_name\n            FROM reviews r\n            JOIN users u ON r.user_id = u.id\n            JOIN carwash c ON r.carwash_id = c.id\n            WHERE r.id = ?\n        ");

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
