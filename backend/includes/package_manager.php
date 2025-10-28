<?php
class PackageManager
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initPackageTable();
    }

    private function initPackageTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS service_packages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            carwash_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            services JSON NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            discount_percentage INT DEFAULT 0,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (carwash_id) REFERENCES carwash(id),
            INDEX (carwash_id),
            INDEX (status)
        )";

        $this->conn->query($sql);
    }

    public function createPackage($data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO service_packages 
            (carwash_id, name, description, services, price, discount_percentage)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $services = json_encode($data['services']);
        $stmt->bind_param(
            'isssdi',
            $data['carwash_id'],
            $data['name'],
            $data['description'],
            $services,
            $data['price'],
            $data['discount_percentage']
        );

        return $stmt->execute();
    }
}
