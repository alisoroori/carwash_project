<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['carwash_id'])) {
        throw new Exception('Unauthorized');
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'set':
            $serviceId = $_POST['service_id'];
            $schedules = json_decode($_POST['schedules'], true);

            // Verify service belongs to this carwash
            $stmt = $conn->prepare("
                SELECT id FROM services 
                WHERE id = ? AND carwash_id = ?
            ");
            $stmt->bind_param('ii', $serviceId, $_SESSION['carwash_id']);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                throw new Exception('Invalid service');
            }

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Clear existing schedules
                $stmt = $conn->prepare("
                    DELETE FROM service_availability 
                    WHERE service_id = ?
                ");
                $stmt->bind_param('i', $serviceId);
                $stmt->execute();

                // Insert new schedules
                $stmt = $conn->prepare("
                    INSERT INTO service_availability 
                    (service_id, day_of_week, start_time, end_time, max_bookings)
                    VALUES (?, ?, ?, ?, ?)
                ");

                foreach ($schedules as $schedule) {
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

                $conn->commit();
                echo json_encode([
                    'success' => true,
                    'message' => 'Availability updated successfully'
                ]);
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;

        case 'get':
            $serviceId = $_GET['service_id'];

            $stmt = $conn->prepare("
                SELECT * FROM service_availability 
                WHERE service_id = ? 
                ORDER BY day_of_week, start_time
            ");

            $stmt->bind_param('i', $serviceId);
            $stmt->execute();
            $result = $stmt->get_result();

            $schedules = [];
            while ($row = $result->fetch_assoc()) {
                $schedules[] = $row;
            }

            echo json_encode([
                'success' => true,
                'schedules' => $schedules
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
