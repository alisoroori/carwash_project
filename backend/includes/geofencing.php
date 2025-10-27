<?php
class GeofencingService
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initGeofenceTable();
    }

    private function initGeofenceTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS service_areas (
            id INT PRIMARY KEY AUTO_INCREMENT,
            carwash_id INT NOT NULL,
            polygon POLYGON NOT NULL,
            name VARCHAR(100),
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (carwash_id) REFERENCES carwash(id),
            SPATIAL INDEX (polygon)
        )";

        $this->conn->query($sql);
    }

    public function isLocationInServiceArea($lat, $lng, $carwashId)
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as is_in_area
            FROM service_areas
            WHERE carwash_id = ?
            AND ST_Contains(polygon, POINT(?, ?))
            AND status = 'active'
        ");

        $stmt->bind_param('idd', $carwashId, $lng, $lat);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();
        return $result['is_in_area'] > 0;
    }
}
