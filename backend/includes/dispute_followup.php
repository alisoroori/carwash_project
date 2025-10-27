<?php
class DisputeFollowup
{
    private $conn;
    private $mailer;

    private $followupSchedule = [
        'initial' => 24, // hours after dispute creation
        'reminder' => 72, // hours after last update
        'escalation' => 168 // hours (1 week) without resolution
    ];

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->mailer = new DisputeCommunicationTemplates();
    }

    public function processFollowups()
    {
        $this->handleInitialFollowups();
        $this->handleReminders();
        $this->handleEscalations();
    }

    private function handleInitialFollowups()
    {
        $query = "
            SELECT d.*, u.email, u.name 
            FROM payment_disputes d
            JOIN users u ON d.user_id = u.id
            WHERE d.status = 'open'
            AND d.created_at <= DATE_SUB(NOW(), INTERVAL ? HOUR)
            AND d.followup_sent = 0
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $this->followupSchedule['initial']);
        $stmt->execute();
        $disputes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($disputes as $dispute) {
            $this->sendFollowup('initial', $dispute);
        }
    }
}
