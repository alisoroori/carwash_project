<?php
declare(strict_types=1);

namespace App\Classes;

class AvailabilityChecker
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initAvailabilityTable();
    }

    private function initAvailabilityTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS location_availability (
            id INT PRIMARY KEY AUTO_INCREMENT,
            carwash_id INT NOT NULL,
            day_of_week TINYINT NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            max_bookings INT DEFAULT 1,
            is_available BOOLEAN DEFAULT true,
            FOREIGN KEY (carwash_id) REFERENCES carwash(id),
            INDEX (day_of_week)
        )";

        $this->conn->query($sql);
    }

    public function checkAvailability($carwashId, $serviceIds, $datetime)
    {
        $result = [
            'available' => false,
            'next_available' => null,
            'reason' => null
        ];

        // Check if carwash is open
        if (!$this->isCarwashOpen($carwashId, $datetime)) {
            $result['reason'] = 'Carwash is closed at this time';
            $result['next_available'] = $this->getNextOpeningTime($carwashId, $datetime);
            return $result;
        }

        // Check staff availability
        $requiredStaff = $this->calculateRequiredStaff($serviceIds);
        if (!$this->hasAvailableStaff($carwashId, $datetime, $requiredStaff)) {
            $result['reason'] = 'No available staff for requested services';
            $result['next_available'] = $this->getNextStaffAvailability($carwashId, $requiredStaff, $datetime);
            return $result;
        }

        // Check equipment availability
        if (!$this->checkEquipmentAvailability($carwashId, $serviceIds, $datetime)) {
            $result['reason'] = 'Required equipment not available';
            $result['next_available'] = $this->getNextEquipmentAvailability($carwashId, $serviceIds, $datetime);
            return $result;
        }

        // Check location availability
        if (!$this->checkLocationAvailability($carwashId, $datetime)) {
            $result['reason'] = 'Location not available';
            $result['next_available'] = $this->getNextLocationAvailability($carwashId, $datetime);
            return $result;
        }

        $result['available'] = true;
        return $result;
    }

    private function isCarwashOpen($carwashId, $datetime)
    {
        $dayOfWeek = date('w', strtotime($datetime));
        $time = date('H:i', strtotime($datetime));

        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as is_open
            FROM working_hours
            WHERE carwash_id = ?
            AND day_of_week = ?
            AND ? BETWEEN opening_time AND closing_time
        ");

        $stmt->bind_param('iis', $carwashId, $dayOfWeek, $time);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['is_open'] > 0;
    }

    private function hasAvailableStaff($carwashId, $datetime, $requiredStaff)
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as available_staff
            FROM staff
            WHERE carwash_id = ?
            AND id NOT IN (
                SELECT staff_id 
                FROM bookings
                WHERE appointment_datetime = ?
                AND status = 'confirmed'
            )
        ");

        $stmt->bind_param('is', $carwashId, $datetime);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['available_staff'] >= $requiredStaff;
    }

    private function checkLocationAvailability($carwashId, $datetime)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                la.*,
                COUNT(b.id) as current_bookings
            FROM location_availability la
            LEFT JOIN bookings b ON b.carwash_id = la.carwash_id 
                AND b.booking_date = DATE(?)
                AND b.booking_time BETWEEN la.start_time AND la.end_time
            WHERE la.carwash_id = ?
                AND la.day_of_week = WEEKDAY(?)
                AND ? BETWEEN la.start_time AND la.end_time
            GROUP BY la.id
            HAVING current_bookings < la.max_bookings
        ");

        $stmt->bind_param('siss', 
            $datetime,
            $carwashId,
            $datetime,
            date('H:i:s', strtotime($datetime))
        );

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

