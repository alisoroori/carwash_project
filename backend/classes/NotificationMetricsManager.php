<?php
declare(strict_types=1);

namespace App\Classes;

class NotificationMetricsManager
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initMetricsTable();
    }

    private function initMetricsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS notification_performance (
            id INT PRIMARY KEY AUTO_INCREMENT,
            notification_id INT NOT NULL,
            delivery_time DECIMAL(10,3), -- in seconds
            processing_time DECIMAL(10,3),
            queue_time DECIMAL(10,3),
            status VARCHAR(50),
            error_code VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (notification_id),
            INDEX (status)
        )";

        $this->conn->query($sql);
    }

    public function trackMetrics($notificationId, $metrics)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO notification_performance 
            (notification_id, delivery_time, processing_time, queue_time, status)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            'iddds',
            $notificationId,
            $metrics['delivery_time'],
            $metrics['processing_time'],
            $metrics['queue_time'],
            $metrics['status']
        );

        return $stmt->execute();
    }

    public function generatePerformanceReport($startDate = null, $endDate = null)
    {
        $query = "
            SELECT 
                AVG(delivery_time) as avg_delivery_time,
                AVG(processing_time) as avg_processing_time,
                AVG(queue_time) as avg_queue_time,
                COUNT(*) as total_notifications,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) / COUNT(*) * 100 as success_rate
            FROM notification_performance
            WHERE created_at BETWEEN ? AND ?
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            'ss',
            $startDate ?? date('Y-m-d', strtotime('-7 days')),
            $endDate ?? date('Y-m-d')
        );

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

