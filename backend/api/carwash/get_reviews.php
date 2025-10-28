<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['carwash_id'])) {
        throw new Exception('Unauthorized');
    }

    $carwashId = $_SESSION['carwash_id'];
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Get reviews with user info and replies
    $stmt = $conn->prepare("
        SELECT 
            r.id,
            r.rating,
            r.comment,
            r.created_at,
            r.reply,
            r.reply_date,
            u.name as user_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.carwash_id = ? 
        AND r.status = 'approved'
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ");

    $stmt->bind_param('iii', $carwashId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    // Get total count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM reviews 
        WHERE carwash_id = ? AND status = 'approved'
    ");

    $stmt->bind_param('i', $carwashId);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];

    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
