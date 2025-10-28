<?php
declare(strict_types=1);

namespace App\Classes;

class NotificationAnalytics {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->initAnalyticsTables();
    }

    private function initAnalyticsTables() {
        $sql = "CREATE TABLE IF NOT EXISTS notification_metrics (
            id INT PRIMARY KEY AUTO_INCREMENT,
            notification_id INT NOT NULL,
            delivery_status ENUM('sent', 'failed', 'pending') NOT NULL,
            delivery_time DATETIME,
            open_time DATETIME,
            channel VARCHAR(50) NOT NULL,
            error_message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (notification_id) REFERENCES notifications(id),
            INDEX (delivery_status),
            INDEX (channel)
        )";
        
        $this->conn->query($sql);
    }

    public function trackDelivery($notificationId, $status, $channel) {
        $stmt = $this->conn->prepare("
            INSERT INTO notification_metrics 
            (notification_id, delivery_status, channel, delivery_time)
            VALUES (?, ?, ?, NOW())
        ");

        $stmt->bind_param('iss', $notificationId, $status, $channel);
        return $stmt->execute();
    }

    public function generateReport($startDate = null, $endDate = null) {
        $query = "
            SELECT 
                channel,
                COUNT(*) as total_sent,
                SUM(CASE WHEN delivery_status = 'sent' THEN 1 ELSE 0 END) as successful,
                AVG(TIMESTAMPDIFF(SECOND, created_at, delivery_time)) as avg_delivery_time
            FROM notification_metrics
            WHERE created_at BETWEEN ? AND ?
            GROUP BY channel
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ss', $startDate ?? date('Y-m-d', strtotime('-30 days')), $endDate ?? date('Y-m-d'));
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
