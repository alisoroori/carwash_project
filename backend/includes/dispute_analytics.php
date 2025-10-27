<?php
class DisputeTrendAnalyzer
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function analyzeTrends($timeframe = 'monthly')
    {
        $groupBy = match ($timeframe) {
            'daily' => 'DATE(created_at)',
            'weekly' => 'YEARWEEK(created_at)',
            'monthly' => 'DATE_FORMAT(created_at, "%Y-%m")'
        };

        $query = "
            SELECT 
                $groupBy as period,
                COUNT(*) as total_disputes,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                AVG(CASE WHEN status = 'resolved' THEN 
                    TIMESTAMPDIFF(HOUR, created_at, resolved_at) 
                END) as avg_resolution_time,
                GROUP_CONCAT(DISTINCT reason) as common_reasons
            FROM payment_disputes
            GROUP BY $groupBy
            ORDER BY period DESC
            LIMIT 12
        ";

        $result = $this->conn->query($query);
        return $this->formatTrendData($result->fetch_all(MYSQLI_ASSOC));
    }

    private function formatTrendData($data)
    {
        return [
            'timeline' => array_column($data, 'period'),
            'metrics' => [
                'total_disputes' => array_column($data, 'total_disputes'),
                'resolution_rate' => array_map(function ($row) {
                    return ($row['resolved'] / $row['total_disputes']) * 100;
                }, $data),
                'avg_resolution_time' => array_column($data, 'avg_resolution_time')
            ],
            'reasons' => $this->analyzeCommonReasons($data)
        ];
    }
}
