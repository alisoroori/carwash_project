<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';

class DisputeReportingAPI
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getDisputeStats($filters = [])
    {
        $where = "WHERE 1=1";
        $params = [];

        if (!empty($filters['date_from'])) {
            $where .= " AND created_at >= ?";
            $params[] = $filters['date_from'];
        }

        $query = "
            SELECT 
                COUNT(*) as total_disputes,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                AVG(CASE WHEN status = 'resolved' AND resolved_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) 
                    ELSE NULL END) as avg_resolution_time
            FROM payment_disputes
            $where
        ";

        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
