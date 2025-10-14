<?php
class MessagePersistenceManager
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initMessageTable();
    }

    private function initMessageTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS persisted_messages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            message_id VARCHAR(36) NOT NULL,
            payload JSON NOT NULL,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_attempt DATETIME,
            INDEX (status, created_at)
        )";

        $this->conn->query($sql);
    }

    public function persistMessage($message)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO persisted_messages 
            (message_id, payload)
            VALUES (?, ?)
        ");

        $messageId = uniqid('msg_', true);
        $payload = json_encode($message);
        $stmt->bind_param('ss', $messageId, $payload);

        return $stmt->execute() ? $messageId : false;
    }
}
