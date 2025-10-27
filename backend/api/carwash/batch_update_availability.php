<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/schedule_validator.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['carwash_id'])) {
        throw new Exception('Unauthorized');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['services']) || !isset($data['schedules'])) {
        throw new Exception('Missing required data');
    }

    $validator = new ScheduleValidator($conn);
    $conn->begin_transaction();

    try {
        // Verify all services belong to this carwash
        $serviceIds = implode(',', array_map('intval', $data['services']));
        $stmt = $conn->prepare("
            SELECT id FROM services 
            WHERE id IN ($serviceIds) 
            AND carwash_id = ?
        ");

        $stmt->bind_param('i', $_SESSION['carwash_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== count($data['services'])) {
            throw new Exception('Invalid service IDs');
        }

        // Check for conflicts
        $conflicts = [];
        foreach ($data['services'] as $serviceId) {
            $serviceConflicts = $validator->detectConflicts($serviceId, $data['schedules']);
            if (!empty($serviceConflicts)) {
                $conflicts[$serviceId] = $serviceConflicts;
            }
        }

        if (!empty($conflicts)) {
            throw new Exception(json_encode([
                'type' => 'schedule_conflict',
                'conflicts' => $conflicts
            ]));
        }

        // Update schedules for all services
        $stmt = $conn->prepare("
            INSERT INTO service_availability 
            (service_id, day_of_week, start_time, end_time, max_bookings)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($data['services'] as $serviceId) {
            // Clear existing schedules
            $clearStmt = $conn->prepare("
                DELETE FROM service_availability WHERE service_id = ?
            ");
            $clearStmt->bind_param('i', $serviceId);
            $clearStmt->execute();

            // Insert new schedules
            foreach ($data['schedules'] as $schedule) {
                $stmt->bind_param(
                    'iissi',
                    $serviceId,
                    $schedule['day'],
                    $schedule['start'],
                    $schedule['end'],
                    $schedule['max']
                );
                $stmt->execute();
            }
        }

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Schedules updated successfully'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    $error = json_decode($e->getMessage(), true);
    if (json_last_error() === JSON_ERROR_NONE && isset($error['type'])) {
        echo json_encode([
            'success' => false,
            'type' => $error['type'],
            'conflicts' => $error['conflicts']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
