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

    // Get average rating and total reviews
    $stmt = $conn->prepare("
        SELECT 
            AVG(rating) as average_rating,
            COUNT(*) as total_reviews,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as recent_reviews,
            SUM(CASE WHEN reply IS NOT NULL THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as response_rate
        FROM reviews
        WHERE carwash_id = ? AND status = 'approved'
    ");

    $stmt->bind_param('i', $carwashId);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
