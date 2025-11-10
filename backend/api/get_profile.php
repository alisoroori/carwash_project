<?php
session_start();
require_once '../includes/db.php';

// NOTE: READ-ONLY GET endpoint
// Returns the authenticated user's profile information. This endpoint
// only reads data and does not mutate server state, therefore it is
// intentionally excluded from CSRF verification. If this becomes a
// mutating endpoint, add require_valid_csrf() from
// backend/includes/csrf_protect.php and remove this exemption.
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    $stmt = $conn->prepare("
        SELECT id, name, email, phone, profile_image 
        FROM users 
        WHERE id = ?
    ");

    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        throw new Exception('User not found');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
