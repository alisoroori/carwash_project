<?php
class ErrorPatternAnalyzer
{
    private $conn;
    private $timeWindow = '24 HOUR';
    private $patternThreshold = 3;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function analyzePatterns()
    {
        // Find recurring errors
        $patterns = $this->findRecurringPatterns();

        // Analyze timing patterns
        $timingPatterns = $this->analyzeTimingPatterns();

        // Identify correlated errors
        $correlations = $this->findErrorCorrelations();

        return [
            'recurring_patterns' => $patterns,
            'timing_patterns' => $timingPatterns,
            'correlations' => $correlations
        ];
    }

    private function findRecurringPatterns()
    {
        $query = "
            SELECT 
                error_type,
                message,
                COUNT(*) as occurrence_count,
                MIN(created_at) as first_seen,
                MAX(created_at) as last_seen
            FROM error_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$this->timeWindow})
            GROUP BY error_type, message
            HAVING COUNT(*) >= ?
            ORDER BY occurrence_count DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $this->patternThreshold);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function analyzeTimingPatterns()
    {
        $query = "
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as error_count,
                AVG(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical_ratio
            FROM error_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$this->timeWindow})
            GROUP BY HOUR(created_at)
            ORDER BY hour
        ";

        return $this->conn->query($query)->fetch_all(MYSQLI_ASSOC);
    }
}
