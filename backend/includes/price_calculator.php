<?php
class DistancePriceCalculator
{
    private $conn;
    private $basePricePerKm = 2.50; // Base rate per kilometer
    private $minimumPrice = 50.00;   // Minimum service price

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initPricingTable();
    }

    private function initPricingTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS distance_pricing (
            id INT PRIMARY KEY AUTO_INCREMENT,
            carwash_id INT NOT NULL,
            zone_km INT NOT NULL,
            price_per_km DECIMAL(10,2) NOT NULL,
            minimum_price DECIMAL(10,2) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (carwash_id) REFERENCES carwash(id)
        )";

        $this->conn->query($sql);
    }

    public function calculatePrice($distance, $carwashId, $serviceId)
    {
        // Get base service price
        $basePrice = $this->getServicePrice($serviceId);

        // Get distance pricing rules
        $rules = $this->getPricingRules($carwashId);

        // Calculate distance surcharge
        $distancePrice = $this->calculateDistanceSurcharge($distance, $rules);

        return [
            'base_price' => $basePrice,
            'distance_surcharge' => $distancePrice,
            'total_price' => $basePrice + $distancePrice
        ];
    }
}
