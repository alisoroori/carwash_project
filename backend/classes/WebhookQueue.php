<?php
declare(strict_types=1);

namespace App\Classes;

class WebhookQueue
{
    private $conn;
    private $table = 'webhook_events';

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initQueue();
    }

    private function initQueue()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT PRIMARY KEY AUTO_INCREMENT,
            event_type VARCHAR(50) NOT NULL,
            payload JSON NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            processed_at DATETIME,
            INDEX (status),
            INDEX (event_type)
        )";

        $this->conn->query($sql);
    }

    public function enqueue($eventType, $payload)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table} (event_type, payload)
            VALUES (?, ?)
        ");

        $jsonPayload = json_encode($payload);
        $stmt->bind_param('ss', $eventType, $jsonPayload);
        return $stmt->execute();
    }

    public function processQueue()
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM {$this->table}
            WHERE status = 'pending'
            AND attempts < 3
            ORDER BY created_at ASC
            LIMIT 10
        ");

        $stmt->execute();
        $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($events as $event) {
            $this->processEvent($event);
        }
    }
}

