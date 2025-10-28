<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Ensure request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

try {
    // Validate inputs
    if (!isset($_POST['date']) || !isset($_POST['service_type']) || !isset($_POST['carwash_id'])) {
        throw new Exception('Missing required parameters: date, service_type, and carwash_id are required');
    }

    // Input sanitization and validation
    $date = htmlspecialchars(trim($_POST['date']), ENT_QUOTES, 'UTF-8');
    $service_type = htmlspecialchars(trim($_POST['service_type']), ENT_QUOTES, 'UTF-8');
    $carwash_id = filter_var($_POST['carwash_id'], FILTER_VALIDATE_INT);

    // Additional validation
    if (!$carwash_id) {
        throw new Exception('Invalid carwash ID');
    }

    // Validate date format (YYYY-MM-DD)
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
        throw new Exception('Invalid date format. Required format: YYYY-MM-DD');
    }

    // Validate service type against allowed values
    $allowed_service_types = ['basic', 'premium', 'deluxe']; // Add your actual service types
    if (!in_array($service_type, $allowed_service_types)) {
        throw new Exception('Invalid service type');
    }

    // Validate date format and ensure it's not in the past
    if (!strtotime($date) || strtotime($date) < strtotime('today')) {
        throw new Exception('Invalid date or date is in the past');
    }

    // Get working hours for the specific carwash
    $day_of_week = date('l', strtotime($date));
    $stmt = $conn->prepare("
        SELECT working_hours, service_duration 
        FROM carwashes c
        JOIN services s ON s.carwash_id = c.id 
        WHERE c.id = ? 
        AND c.status = 'active'
        AND s.type = ?
    ");

    if (!$stmt) {
        throw new Exception('Database query preparation failed');
    }

    $stmt->bind_param('is', $carwash_id, $service_type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Carwash or service not found');
    }

    $row = $result->fetch_assoc();
    $hours = json_decode($row['working_hours'], true);
    $service_duration = $row['service_duration'];

    // Validate working hours data
    if (!isset($hours[$day_of_week])) {
        throw new Exception('Working hours not set for this day');
    }

    if (!$hours[$day_of_week]['is_open']) {
        throw new Exception('Carwash is closed on this day');
    }

    // Generate available time slots
    $available_times = [];
    $interval = max(30, $service_duration); // Use service duration or minimum 30 minutes

    $start = strtotime($hours[$day_of_week]['open']);
    $end = strtotime($hours[$day_of_week]['close']);

    // Prepare booking check statement outside the loop for better performance
    $booking_stmt = $conn->prepare("
        SELECT COUNT(*) as booked 
        FROM bookings 
        WHERE booking_date = ? 
        AND booking_time = ? 
        AND carwash_id = ?
        AND status NOT IN ('cancelled', 'rejected')
    ");

    if (!$booking_stmt) {
        throw new Exception('Failed to prepare booking check query');
    }

    for ($time = $start; $time <= $end - ($service_duration * 60); $time += ($interval * 60)) {
        $slot = date('H:i', $time);

        // Check slot availability
        $booking_stmt->bind_param('ssi', $date, $slot, $carwash_id);
        $booking_stmt->execute();
        $booked = $booking_stmt->get_result()->fetch_assoc()['booked'];

        // Check if slot is available
        if ($booked == 0) {
            // Additional check for overlapping bookings
            $end_time = date('H:i', $time + ($service_duration * 60));
            $available_times[] = [
                'start_time' => $slot,
                'end_time' => $end_time,
                'duration' => $service_duration
            ];
        }
    }

    // Close prepared statements
    $stmt->close();
    $booking_stmt->close();

    echo json_encode([
        'success' => true,
        'times' => $available_times,
        'date' => $date,
        'day' => $day_of_week
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
