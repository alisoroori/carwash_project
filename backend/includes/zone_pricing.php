<?php
class ZonePricingManager
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initZoneTables();
    }

    private function initZoneTables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS price_zones (
            id INT PRIMARY KEY AUTO_INCREMENT,
            carwash_id INT NOT NULL,
            zone_name VARCHAR(100) NOT NULL,
            base_multiplier DECIMAL(4,2) DEFAULT 1.00,
            min_distance INT,
            max_distance INT,
            active BOOLEAN DEFAULT true,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (carwash_id) REFERENCES carwash(id),
            INDEX (carwash_id)
        )";

        $this->conn->query($sql);
    }

    public function calculateZonePrice($basePrice, $distance, $carwashId)
    {
        $stmt = $this->conn->prepare("
            SELECT base_multiplier 
            FROM price_zones 
            WHERE carwash_id = ? 
            AND ? BETWEEN min_distance AND max_distance 
            AND active = true
            LIMIT 1
        ");

        $stmt->bind_param('id', $carwashId, $distance);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $basePrice * ($result['base_multiplier'] ?? 1.00);
    }
}
