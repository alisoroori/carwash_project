<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized');
    }

    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $reason = isset($_GET['reason']) ? $_GET['reason'] : '';

    $query = "
        SELECT 
            rr.*,
            r.rating,
            r.comment,
            u1.name as reporter_name,
            u2.name as review_user_name,
            c.name as carwash_name
        FROM review_reports rr
        JOIN reviews r ON rr.review_id = r.id
        JOIN users u1 ON rr.user_id = u1.id
        JOIN users u2 ON r.user_id = u2.id
        JOIN carwash c ON r.carwash_id = c.id
        WHERE 1=1
    ";

    $params = [];
    $types = '';

    if ($status) {
        $query .= " AND rr.status = ?";
        $params[] = $status;
        $types .= 's';
    }

    if ($reason) {
        $query .= " AND rr.reason = ?";
        $params[] = $reason;
        $types .= 's';
    }

    $query .= " ORDER BY rr.created_at DESC";

    $stmt = $conn->prepare($query);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }

    echo json_encode([
        'success' => true,
        'reports' => $reports
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
