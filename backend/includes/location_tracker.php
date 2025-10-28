<?php
class LocationTracker
{
    private $conn;
    private $table = 'service_locations';

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initTrackingTable();
    }

    private function initTrackingTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT PRIMARY KEY AUTO_INCREMENT,
            service_id INT NOT NULL,
            latitude DECIMAL(10,8) NOT NULL,
            longitude DECIMAL(11,8) NOT NULL,
            status VARCHAR(50),
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (service_id) REFERENCES services(id),
            INDEX (service_id),
            INDEX (timestamp)
        )";

        $this->conn->query($sql);
    }

    public function updateLocation($serviceId, $lat, $lng, $status = 'active')
    {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table} 
            (service_id, latitude, longitude, status)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param('idds', $serviceId, $lat, $lng, $status);
        return $stmt->execute();
    }
}
