<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized');
    }

    if (!isset($_GET['id'])) {
        throw new Exception('Report ID is required');
    }

    $stmt = $conn->prepare("
        SELECT 
            rr.*,
            r.rating,
            r.comment,
            r.created_at as review_date,
            u1.name as reporter_name,
            u2.name as review_user_name,
            c.name as carwash_name
        FROM review_reports rr
        JOIN reviews r ON rr.review_id = r.id
        JOIN users u1 ON rr.user_id = u1.id
        JOIN users u2 ON r.user_id = u2.id
        JOIN carwash c ON r.carwash_id = c.id
        WHERE rr.id = ?
    ");

    $stmt->bind_param('i', $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($report = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'report' => $report
        ]);
    } else {
        throw new Exception('Report not found');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
