<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';

header('Content-Type: application/json');
// This endpoint is query-based (GET); CSRF is not required for safe GETs.
// Ensure only GET is used for read-only requests. No CSRF check added.

try {
    if (!isset($_GET['service_id']) || !isset($_GET['date'])) {
        throw new Exception('Missing required parameters');
    }

    $service_id = filter_var($_GET['service_id'], FILTER_VALIDATE_INT);
    $date = date('Y-m-d', strtotime($_GET['date']));

    // Get service details
    $stmt = $conn->prepare("
        SELECT duration FROM services WHERE id = ? AND status = 'active'
    ");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch();

    if (!$service) {
        throw new Exception('Service not found');
    }

    // Get booked slots
    $stmt = $conn->prepare("
        SELECT booking_time 
        FROM bookings 
        WHERE service_id = ? 
        AND booking_date = ?
        AND status != 'cancelled'
    ");
    $stmt->execute([$service_id, $date]);
    $booked = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $booked[] = $row['booking_time'];
    }

    // Generate available slots
    $slots = [];
    $start = strtotime('09:00');
    $end = strtotime('17:00');
    $duration = $service['duration'] * 60;

    for ($time = $start; $time <= $end - $duration; $time += $duration) {
        $slot = date('H:i:s', $time);
        if (!in_array($slot, $booked)) {
            $slots[] = [
                'time' => $slot,
                'end_time' => date('H:i:s', $time + $duration)
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'slots' => $slots
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
