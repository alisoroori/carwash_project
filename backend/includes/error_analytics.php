<?php
class ErrorAnalytics {
    private $conn;
    private $errorTypes = [
        'sync_error' => 1,
        'payment_error' => 2,
        'system_error' => 3
    ];

    public function __construct($conn) {
        $this->conn = $conn;
        $this->initErrorTables();
    }

    private function initErrorTables() {
        $sql = "CREATE TABLE IF NOT EXISTS error_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            error_type INT,
            severity ENUM('low', 'medium', 'high', 'critical'),
            message TEXT,
            stack_trace TEXT,
            context JSON,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME,
            resolution_notes TEXT,
            INDEX (error_type),
            INDEX (severity)
        )";
        
        $this->conn->query($sql);
    }

    public function logError($type, $severity, $message, $context = []) {
        $stmt = $this->conn->prepare("
            INSERT INTO error_logs 
            (error_type, severity, message, context)
            VALUES (?, ?, ?, ?)
        ");

        $jsonContext = json_encode($context);
        $errorTypeId = $this->errorTypes[$type] ?? 3;
        
        $stmt->bind_param('isss', $errorTypeId, $severity, $message, $jsonContext);
        return $stmt->execute();
    }

    public function generateErrorReport($startDate = null, $endDate = null) {
        // Query for error statistics and patterns
        $query = "
            SELECT 
                error_type,
                severity,
                COUNT(*) as occurrence_count,
                AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_resolution_time
            FROM error_logs
            WHERE (?1 IS NULL OR created_at >= ?1)
            AND (?2 IS NULL OR created_at <= ?2)
            GROUP BY error_type, severity
            ORDER BY occurrence_count DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
