﻿<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Verify admin authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Unauthorized access']));
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Get and validate date range
    $period = trim(filter_input(INPUT_GET, 'period', FILTER_DEFAULT)) ?: 'month';
    if (!in_array($period, ['day', 'week', 'month', 'year'])) {
        throw new Exception('Invalid period specified');
    }

    $endDate = new DateTime();
    $startDate = new DateTime();

    switch ($period) {
        case 'day':
            $startDate->modify('-1 day');
            break;
        case 'week':
            $startDate->modify('-1 week');
            break;
        case 'month':
            $startDate->modify('-1 month');
            break;
        case 'year':
            $startDate->modify('-1 year');
            break;
    }

    // Get report data
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as total_bookings,
            SUM(total_amount) as revenue,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
        FROM bookings 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");

    $stmt->execute([
        $startDate->format('Y-m-d 00:00:00'),
        $endDate->format('Y-m-d 23:59:59')
    ]);

    echo json_encode([
        'success' => true,
        'data' => [
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'report' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
