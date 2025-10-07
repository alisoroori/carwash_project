<?php
require_once '../../includes/db.php';
require_once '../../includes/config.php';

header('Content-Type: application/json');

try {
    // Validate required parameters
    if (!isset($_GET['service_id']) || !isset($_GET['date'])) {
        throw new Exception('Missing required parameters: service_id and date');
    }

    // Sanitize inputs
    $service_id = filter_var($_GET['service_id'], FILTER_VALIDATE_INT);
    $date = date('Y-m-d', strtotime($_GET['date']));

    if (!$service_id || !$date) {
        throw new Exception('Invalid parameters');
    }

    // Validate date is not in past
    if (strtotime($date) < strtotime('today')) {
        throw new Exception('Cannot book for past dates');
    }

    // Get service details and carwash working hours
    $stmt = $conn->prepare("
        SELECT s.*, c.id as carwash_id 
        FROM services s
        JOIN carwash c ON s.carwash_id = c.id
        WHERE s.id = ? AND s.status = 'active'
    ");

    $stmt->bind_param('i', $service_id);
    $stmt->execute();
    $service = $stmt->get_result()->fetch_assoc();

    if (!$service) {
        throw new Exception('Service not found or inactive');
    }

    // Define working hours (can be moved to database)
    $working_hours = [
        'start' => '09:00:00',
        'end' => '17:00:00',
        'interval' => $service['duration'] // in minutes
    ];

    // Get existing bookings for the date
    $stmt = $conn->prepare("
        SELECT booking_time 
        FROM bookings 
        WHERE service_id = ? 
        AND booking_date = ?
        AND status != 'cancelled'
    ");

    $stmt->bind_param('is', $service_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $booked_slots = [];
    while ($row = $result->fetch_assoc()) {
        $booked_slots[] = $row['booking_time'];
    }

    // Generate available timeslots
    $available_slots = [];
    $current_time = strtotime($working_hours['start']);
    $end_time = strtotime($working_hours['end']);
    $interval = $working_hours['interval'] * 60; // Convert to seconds

    while ($current_time + $interval <= $end_time) {
        $slot_time = date('H:i:s', $current_time);
        
        // Check if slot is already booked
        if (!in_array($slot_time, $booked_slots)) {
            // For same-day bookings, check if slot is in future
            if ($date === date('Y-m-d') && strtotime($slot_time) <= time()) {
                $current_time += $interval;
                continue;
            }

            $available_slots[] = [
                'time' => $slot_time,
                'end_time' => date('H:i:s', $current_time + $interval),
                'duration' => $service['duration']
            ];
        }
        
        $current_time += $interval;
    }

    echo json_encode([
        'success' => true,
        'date' => $date,
        'service_id' => $service_id,
        'timeslots' => $available_slots
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}