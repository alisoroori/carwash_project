<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';

header('Content-Type: application/json');

try {
    // Validate required parameters
    if (!isset($_GET['date']) || !isset($_GET['service_id'])) {
        throw new Exception('Missing required parameters');
    }

    // Sanitize inputs
    $date = filter_var($_GET['date'], FILTER_SANITIZE_STRING);
    $service_id = filter_var($_GET['service_id'], FILTER_VALIDATE_INT);

    if (!$service_id || !strtotime($date)) {
        throw new Exception('Invalid parameters');
    }

    // Get service details
    $stmt = $conn->prepare("
        SELECT s.duration, c.id as carwash_id
        FROM services s
        JOIN carwash c ON s.carwash_id = c.id
        WHERE s.id = ?
    ");

    if (!$stmt) {
        throw new Exception('Failed to prepare service query');
    }

    $stmt->bind_param('i', $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Service not found');
    }

    $service = $result->fetch_assoc();

    // Generate available timeslots
    $timeslots = generateTimeslots($date, $service['duration'], $service['carwash_id']);

    echo json_encode([
        'success' => true,
        'timeslots' => $timeslots
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function generateTimeslots($date, $duration, $carwash_id) {
    global $conn;
    
    // Get business hours (example: 9:00 - 17:00)
    $start_time = '09:00';
    $end_time = '17:00';
    
    $start = strtotime($date . ' ' . $start_time);
    $end = strtotime($date . ' ' . $end_time);
    $duration_seconds = $duration * 60; // Convert minutes to seconds
    
    $timeslots = [];
    
    // Generate all possible timeslots
    for ($time = $start; $time <= $end - $duration_seconds; $time += $duration_seconds) {
        $slot_start = date('H:i', $time);
        $slot_end = date('H:i', $time + $duration_seconds);
        
        // Check if slot is available
        $stmt = $conn->prepare("
            SELECT COUNT(*) as booked
            FROM bookings
            WHERE carwash_id = ?
            AND date = ?
            AND time = ?
            AND status != 'cancelled'
        ");
        
        $slot_date = date('Y-m-d', strtotime($date));
        $stmt->bind_param('iss', $carwash_id, $slot_date, $slot_start);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['booked'] == 0) {
            $timeslots[] = [
                'start' => $slot_start,
                'end' => $slot_end
            ];
        }
    }
    
    return $timeslots;
}