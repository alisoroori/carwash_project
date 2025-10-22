<?php
declare(strict_types=1);

namespace App\Classes;

class ServiceScheduler
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function calculateServiceTime($serviceIds)
    {
        $stmt = $conn->prepare("
            SELECT SUM(duration) as total_duration
            FROM services
            WHERE id IN (" . implode(',', $serviceIds) . ")
        ");

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total_duration'];
    }

    public function getAvailableSlots($carwashId, $date, $serviceIds)
    {
        // Get carwash working hours
        $workingHours = $this->getWorkingHours($carwashId, date('w', strtotime($date)));

        // Calculate total service duration
        $serviceDuration = $this->calculateServiceTime($serviceIds);

        // Get existing bookings
        $bookedSlots = $this->getBookedSlots($carwashId, $date);

        // Generate available time slots
        $availableSlots = [];
        $currentTime = strtotime($workingHours['start_time']);
        $endTime = strtotime($workingHours['end_time']);

        while ($currentTime + ($serviceDuration * 60) <= $endTime) {
            $slotStart = date('H:i', $currentTime);
            $slotEnd = date('H:i', $currentTime + ($serviceDuration * 60));

            // Check if slot is available
            if (!$this->isSlotBooked($slotStart, $slotEnd, $bookedSlots)) {
                $availableSlots[] = [
                    'start' => $slotStart,
                    'end' => $slotEnd
                ];
            }

            $currentTime += 30 * 60; // 30-minute intervals
        }

        return $availableSlots;
    }

    private function isSlotBooked($start, $end, $bookedSlots)
    {
        foreach ($bookedSlots as $slot) {
            if (
                ($start >= $slot['start'] && $start < $slot['end']) ||
                ($end > $slot['start'] && $end <= $slot['end']) ||
                ($start <= $slot['start'] && $end >= $slot['end'])
            ) {
                return true;
            }
        }
        return false;
    }
}

