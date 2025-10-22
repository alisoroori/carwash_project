<?php
declare(strict_types=1);

namespace App\Classes;

class DynamicPricingManager
{
    private $conn;
    private $baseMultiplier = 1.0;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initPricingTable();
    }

    private function initPricingTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS dynamic_pricing (
            id INT PRIMARY KEY AUTO_INCREMENT,
            carwash_id INT NOT NULL,
            time_slot VARCHAR(20) NOT NULL,
            day_of_week INT NOT NULL,
            multiplier DECIMAL(3,2) NOT NULL,
            FOREIGN KEY (carwash_id) REFERENCES carwash(id),
            UNIQUE KEY unique_timing (carwash_id, time_slot, day_of_week)
        )";

        $this->conn->query($sql);
    }

    public function calculateDynamicPrice($serviceId, $datetime)
    {
        $basePrice = $this->getBasePrice($serviceId);
        $multiplier = $this->getPriceMultiplier($datetime);
        $demandMultiplier = $this->getDemandMultiplier($serviceId, $datetime);

        return $basePrice * $multiplier * $demandMultiplier;
    }

    private function getDemandMultiplier($serviceId, $datetime)
    {
        // Calculate based on bookings in similar time slots
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as booking_count
            FROM bookings
            WHERE service_id = ?
            AND DATE(appointment_datetime) = DATE(?)
            AND HOUR(appointment_datetime) = HOUR(?)
        ");

        $stmt->bind_param('iss', $serviceId, $datetime, $datetime);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        // Increase price by 10% for every 3 bookings in the same hour
        return 1 + (floor($result['booking_count'] / 3) * 0.1);
    }
}

