<?php
declare(strict_types=1);

namespace App\Classes;

class RefundRulesConfig
{
    private $conn;
    private $rules = [];

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initRulesTable();
        $this->loadRules();
    }

    private function initRulesTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS refund_rules (
            id INT PRIMARY KEY AUTO_INCREMENT,
            rule_name VARCHAR(100) NOT NULL,
            time_limit INT NOT NULL, -- in hours
            amount_limit DECIMAL(10,2),
            auto_approve BOOLEAN DEFAULT FALSE,
            conditions JSON,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";

        $this->conn->query($sql);
    }

    public function evaluateRefund($bookingId, $amount, $reason)
    {
        $booking = $this->getBookingDetails($bookingId);

        foreach ($this->rules as $rule) {
            if ($this->matchesRule($rule, $booking, $amount)) {
                return [
                    'eligible' => true,
                    'auto_approve' => $rule['auto_approve'],
                    'rule_id' => $rule['id']
                ];
            }
        }

        return ['eligible' => false, 'reason' => 'No matching refund rule found'];
    }
}

