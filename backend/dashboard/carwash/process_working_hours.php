<?php
session_start();
require_once '../../includes/db.php';

// Set JSON response header
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'carwash') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get carwash ID
    $stmt = $conn->prepare("SELECT id FROM carwashes WHERE owner_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $carwash = $stmt->get_result()->fetch_assoc();

    if (!$carwash) {
        throw new Exception('Carwash not found');
    }

    // Days of the week
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    // Validate and process each day
    foreach ($days as $day) {
        $is_open = isset($_POST['is_open'][$day]) ? 1 : 0;
        $open_time = filter_var($_POST['open_time'][$day], FILTER_SANITIZE_STRING);
        $close_time = filter_var($_POST['close_time'][$day], FILTER_SANITIZE_STRING);

        // Validate times if day is open
        if ($is_open) {
            if (!validateTime($open_time) || !validateTime($close_time)) {
                throw new Exception("Invalid time format for $day");
            }

            if (strtotime($close_time) <= strtotime($open_time)) {
                throw new Exception("Closing time must be after opening time for $day");
            }
        }

        // Check if record exists
        $stmt = $conn->prepare("
            SELECT id FROM working_hours 
            WHERE carwash_id = ? AND day = ?
        ");
        $stmt->bind_param("is", $carwash['id'], $day);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();

        if ($existing) {
            // Update existing record
            $stmt = $conn->prepare("
                UPDATE working_hours 
                SET is_open = ?,
                    open_time = ?,
                    close_time = ?,
                    updated_at = NOW()
                WHERE carwash_id = ? AND day = ?
            ");
            $stmt->bind_param("issss", $is_open, $open_time, $close_time, $carwash['id'], $day);
        } else {
            // Insert new record
            $stmt = $conn->prepare("
                INSERT INTO working_hours (
                    carwash_id, 
                    day, 
                    is_open, 
                    open_time, 
                    close_time,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("isiss", $carwash['id'], $day, $is_open, $open_time, $close_time);
        }

        if (!$stmt->execute()) {
            throw new Exception("Failed to save working hours for $day");
        }
    }

    // Update carwash working_hours field
    $stmt = $conn->prepare("
        UPDATE carwashes 
        SET working_hours = ? 
        WHERE id = ?
    ");

    // Create summary string
    $summary = generateWorkingHoursSummary($_POST['is_open'], $_POST['open_time'], $_POST['close_time']);
    $stmt->bind_param("si", $summary, $carwash['id']);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Working hours updated successfully'
    ]);
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Validate time format (HH:MM)
 */
function validateTime($time)
{
    return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time);
}

/**
 * Generate working hours summary string
 */
function generateWorkingHoursSummary($is_open, $open_times, $close_times)
{
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $dayNames = [
        'Monday' => 'Pzt',
        'Tuesday' => 'Sal',
        'Wednesday' => 'Çar',
        'Thursday' => 'Per',
        'Friday' => 'Cum',
        'Saturday' => 'Cmt',
        'Sunday' => 'Paz'
    ];

    $summary = [];
    $current_group = [];

    foreach ($days as $day) {
        if (isset($is_open[$day]) && $open_times[$day] === $current_group['time'] ?? null) {
            $current_group['days'][] = $dayNames[$day];
        } else {
            if (!empty($current_group)) {
                $summary[] = implode('-', array_filter([
                    reset($current_group['days']),
                    end($current_group['days'])
                ])) . ' ' . $current_group['time'];
            }
            $current_group = isset($is_open[$day]) ? [
                'days' => [$dayNames[$day]],
                'time' => $open_times[$day] . '-' . $close_times[$day]
            ] : [];
        }
    }

    if (!empty($current_group)) {
        $summary[] = implode('-', array_filter([
            reset($current_group['days']),
            end($current_group['days'])
        ])) . ' ' . $current_group['time'];
    }

    return implode(', ', $summary) ?: 'Kapalı';
}
