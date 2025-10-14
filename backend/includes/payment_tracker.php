<?php
class PaymentTracker {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->initTrackingTable();
    }

    private function initTrackingTable() {
        $sql = "CREATE TABLE IF NOT EXISTS payment_tracking (
            id INT PRIMARY KEY AUTO_INCREMENT,
            payment_id VARCHAR(100) NOT NULL,
            booking_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            tracking_data JSON,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id),
            INDEX (payment_id),
            INDEX (status)
        )";
        
        $this->conn->query($sql);
    }

    public function trackPayment($paymentId, $status, $data = []) {
        $stmt = $this->conn->prepare("
            INSERT INTO payment_tracking 
            (payment_id, booking_id, status, amount, tracking_data)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            tracking_data = JSON_MERGE_PATCH(tracking_data, VALUES(tracking_data)),
            updated_at = NOW()
        ");

        $trackingData = json_encode($data);
        $stmt->bind_param('sisds', 
            $paymentId,
            $data['booking_id'],
            $status,
            $data['amount'],
            $trackingData
        );

        return $stmt->execute();
    }
}