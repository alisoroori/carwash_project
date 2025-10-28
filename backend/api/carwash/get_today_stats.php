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
    $today = date('Y-m-d');

    // Get today's appointments count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM appointments 
        WHERE carwash_id = ? 
        AND DATE(appointment_date) = ?
    ");
    $stmt->bind_param('is', $carwashId, $today);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_assoc()['count'];

    // Get today's revenue
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE carwash_id = ? 
        AND DATE(created_at) = ?
    ");
    $stmt->bind_param('is', $carwashId, $today);
    $stmt->execute();
    $revenue = $stmt->get_result()->fetch_assoc()['total'];

    // Get average rating
    $stmt = $conn->prepare("
        SELECT AVG(rating) as avg_rating 
        FROM reviews 
        WHERE carwash_id = ? 
        AND status = 'approved'
    ");
    $stmt->bind_param('i', $carwashId);
    $stmt->execute();
    $rating = $stmt->get_result()->fetch_assoc()['avg_rating'];

    // Get monthly revenue
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE carwash_id = ? 
        AND MONTH(created_at) = MONTH(CURRENT_DATE())
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ");
    $stmt->bind_param('i', $carwashId);
    $stmt->execute();
    $monthly = $stmt->get_result()->fetch_assoc()['total'];

    echo json_encode([
        'success' => true,
        'stats' => [
            'appointments' => $appointments,
            'revenue' => $revenue,
            'rating' => number_format($rating, 1),
            'monthly' => $monthly
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
