<?php
class SyncRecoveryManager
{
    private $conn;
    private $maxRetries = 3;
    private $backoffTime = 300; // 5 minutes

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initErrorLog();
    }

    private function initErrorLog()
    {
        $sql = "CREATE TABLE IF NOT EXISTS sync_errors (
            id INT PRIMARY KEY AUTO_INCREMENT,
            sync_type VARCHAR(50),
            entity_id VARCHAR(100),
            error_message TEXT,
            retry_count INT DEFAULT 0,
            last_attempt DATETIME,
            status ENUM('pending', 'retrying', 'resolved', 'failed'),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";

        $this->conn->query($sql);
    }

    public function handleSyncError($syncType, $entityId, $error)
    {
        try {
            $this->conn->begin_transaction();

            // Log error
            $stmt = $this->conn->prepare("
                INSERT INTO sync_errors 
                (sync_type, entity_id, error_message, status)
                VALUES (?, ?, ?, 'pending')
            ");

            $stmt->bind_param('sss', $syncType, $entityId, $error);
            $stmt->execute();

            // Schedule retry
            $this->scheduleRetry($this->conn->insert_id);

            $this->conn->commit();
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Failed to handle sync error: " . $e->getMessage());
        }
    }

    public function processRetries()
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM sync_errors 
            WHERE status = 'pending'
            AND retry_count < ?
            AND (last_attempt IS NULL OR 
                 last_attempt <= DATE_SUB(NOW(), INTERVAL ? SECOND))
        ");

        $stmt->bind_param('ii', $this->maxRetries, $this->backoffTime);
        $stmt->execute();
        $errors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($errors as $error) {
            $this->attemptRecovery($error);
        }
    }
}
