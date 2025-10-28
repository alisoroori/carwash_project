<?php
declare(strict_types=1);

namespace App\Classes;

class TicketEscalationManager
{
    private $conn;
    private $rules;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initEscalationRules();
    }

    private function initEscalationRules()
    {
        $this->rules = [
            'high_value' => [
                'condition' => 'amount > 1000',
                'time_limit' => 4, // hours
                'escalation_level' => 'manager'
            ],
            'multiple_disputes' => [
                'condition' => 'user_dispute_count >= 3',
                'time_limit' => 24,
                'escalation_level' => 'fraud_team'
            ],
            'urgent_response' => [
                'condition' => 'status = "pending" AND age > 48',
                'escalation_level' => 'supervisor'
            ]
        ];
    }

    public function checkEscalations()
    {
        foreach ($this->rules as $ruleId => $rule) {
            $tickets = $this->findTicketsForRule($rule);
            foreach ($tickets as $ticket) {
                $this->escalateTicket($ticket, $rule);
            }
        }
    }

    private function findTicketsForRule($rule)
    {
        $query = "
            SELECT t.*, 
                   COUNT(pd.id) as user_dispute_count,
                   TIMESTAMPDIFF(HOUR, t.created_at, NOW()) as age
            FROM support_tickets t
            LEFT JOIN payment_disputes pd ON t.user_id = pd.user_id
            WHERE {$rule['condition']}
            GROUP BY t.id
        ";

        return $this->conn->query($query)->fetch_all(MYSQLI_ASSOC);
    }
}

