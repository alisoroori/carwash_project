<?php
declare(strict_types=1);

namespace App\Classes;

class ScheduleValidator
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function detectConflicts($serviceId, $schedules)
    {
        $conflicts = [];

        foreach ($schedules as $newSchedule) {
            // Check overlap with existing schedules
            $stmt = $this->conn->prepare("
                SELECT sa.*, s.name as service_name
                FROM service_availability sa
                JOIN services s ON sa.service_id = s.id
                WHERE s.carwash_id = (
                    SELECT carwash_id FROM services WHERE id = ?
                )
                AND sa.day_of_week = ?
                AND (
                    (sa.start_time < ? AND sa.end_time > ?)
                    OR
                    (sa.start_time < ? AND sa.end_time > ?)
                    OR
                    (sa.start_time >= ? AND sa.end_time <= ?)
                )
            ");

            $stmt->bind_param(
                'iissssss',
                $serviceId,
                $newSchedule['day'],
                $newSchedule['end'],
                $newSchedule['start'],
                $newSchedule['start'],
                $newSchedule['start'],
                $newSchedule['start'],
                $newSchedule['end']
            );

            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $conflicts[] = [
                    'day' => $row['day_of_week'],
                    'start' => $row['start_time'],
                    'end' => $row['end_time'],
                    'service' => $row['service_name']
                ];
            }
        }

        return $conflicts;
    }
}

