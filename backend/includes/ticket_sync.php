<?php
require_once 'db.php';
require_once 'support_ticket_integration.php';
class TicketSynchronizer
{
    private $conn;
    private $supportAPI;
    private $syncInterval = 300; // 5 minutes

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->supportAPI = new SupportTicketIntegration($conn);
        $this->initSyncTable();
    }

    private function initSyncTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS ticket_sync_status (
            ticket_id VARCHAR(100) PRIMARY KEY,
            local_id INT,
            last_sync DATETIME,
            status VARCHAR(50),
            sync_hash VARCHAR(64),
            FOREIGN KEY (local_id) REFERENCES payment_disputes(id)
        )";

        $this->conn->query($sql);
    }

    public function syncTickets()
    {
        $lastSync = $this->getLastSyncTime();

        if (time() - strtotime($lastSync) < $this->syncInterval) {
            return;
        }

        // Get updates from support system
        $updatedTickets = $this->supportAPI->getUpdatedTickets($lastSync);

        foreach ($updatedTickets as $ticket) {
            $this->processTicketUpdate($ticket);
        }

        $this->updateLastSyncTime();
    }

    private function processTicketUpdate($ticket)
    {
        $stmt = $this->conn->prepare("
            UPDATE payment_disputes 
            SET status = ?, 
                resolution_notes = ?,
                updated_at = NOW()
            WHERE id = (
                SELECT local_id 
                FROM ticket_sync_status 
                WHERE ticket_id = ?
            )
        ");

        $stmt->bind_param(
            'sss',
            $this->mapTicketStatus($ticket['status']),
            $ticket['notes'],
            $ticket['id']
        );

        return $stmt->execute();
    }
}
