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

    // Get last 7 days performance
    $stmt = $conn->prepare("
        SELECT 
            DATE(a.appointment_date) as date,
            COUNT(a.id) as total_appointments,
            SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            COALESCE(SUM(p.amount), 0) as revenue
        FROM appointments a
        LEFT JOIN payments p ON a.id = p.appointment_id
        WHERE a.carwash_id = ?
        AND a.appointment_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
        GROUP BY DATE(a.appointment_date)
        ORDER BY date ASC
    ");

    $stmt->bind_param('i', $carwashId);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
