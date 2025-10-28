<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }

    $stmt = $conn->prepare("
        SELECT 
            b.id,
            b.service_type as service_name,
            b.booking_date as date,
            b.booking_time as time,
                b.status,
                b.price,
                c.business_name as carwash_name
            FROM bookings b
            JOIN carwash_profiles c ON b.carwash_id = c.id
            WHERE b.user_id = ?
        ORDER BY b.booking_date DESC, b.booking_time DESC
    ");

    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }

    echo json_encode([
        'success' => true,
        'reservations' => $reservations
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
