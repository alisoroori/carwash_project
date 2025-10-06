<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    // Validate inputs
    if (!isset($_POST['date']) || !isset($_POST['service_type'])) {
        throw new Exception('Missing required parameters');
    }

    $date = filter_var($_POST['date'], FILTER_SANITIZE_STRING);
    $service_type = filter_var($_POST['service_type'], FILTER_SANITIZE_STRING);

    // Get working hours for the selected date
    $day_of_week = date('l', strtotime($date));

    $stmt = $conn->prepare("
        SELECT working_hours 
        FROM carwashes 
        WHERE status = 'active'
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    // Generate available time slots
    $available_times = [];
    $interval = 30; // minutes

    while ($row = $result->fetch_assoc()) {
        $hours = json_decode($row['working_hours'], true);

        if (isset($hours[$day_of_week]) && $hours[$day_of_week]['is_open']) {
            $start = strtotime($hours[$day_of_week]['open']);
            $end = strtotime($hours[$day_of_week]['close']);

            for ($time = $start; $time <= $end; $time += ($interval * 60)) {
                $slot = date('H:i', $time);

                // Check if slot is already booked
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as booked 
                    FROM bookings 
                    WHERE booking_date = ? 
                    AND booking_time = ? 
                    AND status != 'cancelled'
                ");
                $stmt->bind_param('ss', $date, $slot);
                $stmt->execute();
                $booked = $stmt->get_result()->fetch_assoc()['booked'];

                if ($booked == 0) {
                    $available_times[] = $slot;
                }
            }
        }
    }

    // Sort times
    sort($available_times);

    echo json_encode([
        'success' => true,
        'times' => $available_times
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
