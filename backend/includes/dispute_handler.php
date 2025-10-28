<?php
class DisputeHandler
{
    private $conn;
    private $mailer;
    private $notifier;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initDisputeTable();
    }

    private function initDisputeTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS payment_disputes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            transaction_id INT NOT NULL,
            user_id INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            evidence TEXT,
            status ENUM('open', 'investigating', 'resolved', 'rejected') DEFAULT 'open',
            resolution_notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME,
            FOREIGN KEY (transaction_id) REFERENCES transactions(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";

        $this->conn->query($sql);
    }

    public function createDispute($data)
    {
        try {
            $this->conn->begin_transaction();

            $stmt = $this->conn->prepare("
                INSERT INTO payment_disputes 
                (transaction_id, user_id, reason, evidence)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->bind_param(
                'iiss',
                $data['transaction_id'],
                $data['user_id'],
                $data['reason'],
                $data['evidence']
            );

            $stmt->execute();
            $disputeId = $this->conn->insert_id;

            // Notify relevant parties
            $this->notifyDisputeCreated($disputeId);

            $this->conn->commit();
            return ['success' => true, 'dispute_id' => $disputeId];
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function updateDisputeStatus($disputeId, $status, $notes = '')
    {
        $stmt = $this->conn->prepare("
            UPDATE payment_disputes 
            SET status = ?,
                resolution_notes = ?,
                resolved_at = CASE WHEN ? IN ('resolved', 'rejected') THEN NOW() ELSE NULL END
            WHERE id = ?
        ");

        $stmt->bind_param('sssi', $status, $notes, $status, $disputeId);

        if ($stmt->execute()) {
            $this->notifyDisputeUpdated($disputeId, $status);
            return true;
        }
        return false;
    }
}
