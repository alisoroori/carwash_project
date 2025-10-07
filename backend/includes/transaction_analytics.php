<?php
class TransactionAnalytics
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getTransactionStats($filters = [])
    {
        $where = "WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($filters['start_date'])) {
            $where .= " AND transaction_date >= ?";
            $params[] = $filters['start_date'];
            $types .= "s";
        }

        $query = "
            SELECT 
                COUNT(*) as total_transactions,
                SUM(amount) as total_revenue,
                AVG(amount) as avg_transaction_value,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_transactions,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_transactions
            FROM transactions
            $where
        ";

        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getRevenueByPeriod($period = 'daily')
    {
        $groupBy = match ($period) {
            'daily' => 'DATE(transaction_date)',
            'weekly' => 'YEARWEEK(transaction_date)',
            'monthly' => 'DATE_FORMAT(transaction_date, "%Y-%m")'
        };

        $query = "
            SELECT 
                $groupBy as period,
                COUNT(*) as transactions,
                SUM(amount) as revenue,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful
            FROM transactions
            WHERE status IN ('completed', 'failed')
            GROUP BY $groupBy
            ORDER BY period DESC
            LIMIT 30
        ";

        return $this->conn->query($query)->fetch_all(MYSQLI_ASSOC);
    }
}
