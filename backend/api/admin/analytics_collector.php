﻿<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

class AnalyticsCollector
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->ensureAnalyticsTables();
    }

    protected function ensureAnalyticsTables()
    {
        $tables = [
            'analytics_events' => "
                CREATE TABLE IF NOT EXISTS analytics_events (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    event_type VARCHAR(50) NOT NULL,
                    event_data JSON,
                    user_id INT,
                    ip_address VARCHAR(45),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX (event_type),
                    INDEX (created_at)
                )",
            'user_sessions' => "
                CREATE TABLE IF NOT EXISTS user_sessions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT,
                    session_id VARCHAR(255),
                    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_activity DATETIME,
                    ended_at DATETIME,
                    INDEX (user_id),
                    INDEX (session_id)
                )"
        ];

        foreach ($tables as $sql) {
            $this->conn->query($sql);
        }
    }

    public function trackEvent($type, $data = [])
    {
        $stmt = $this->conn->prepare("
            INSERT INTO analytics_events 
            (event_type, event_data, user_id, ip_address)
            VALUES (?, ?, ?, ?)
        ");

        $userId = $_SESSION['user_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $jsonData = json_encode($data);

        $stmt->bind_param('ssis', $type, $jsonData, $userId, $ipAddress);
        return $stmt->execute();
    }

    public function updateSession()
    {
        $stmt = $this->conn->prepare("
            UPDATE user_sessions 
            SET last_activity = NOW()
            WHERE session_id = ?
        ");

        $stmt->bind_param('s', session_id());
        return $stmt->execute();
    }

    public function getActiveUsers($minutes = 5)
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(DISTINCT session_id) as count
            FROM user_sessions
            WHERE last_activity >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            AND ended_at IS NULL
        ");

        $stmt->bind_param('i', $minutes);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['count'];
    }

    public function collectRatingStats()
    {
        $stats = [
            'distribution' => $this->getRatingDistribution(),
            'trends' => $this->getRatingTrends(),
            'topRated' => $this->getTopRatedCarwashes(),
            'recentReviews' => $this->getRecentReviews()
        ];

        return $stats;
    }

    private function getRatingDistribution()
    {
        $stmt = $this->conn->prepare("
            SELECT 
                rating,
                COUNT(*) as count
            FROM reviews
            WHERE status = 'approved'
            GROUP BY rating
            ORDER BY rating
        ");

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function getRatingTrends()
    {
        $stmt = $this->conn->prepare("
            SELECT 
                DATE(created_at) as date,
                AVG(rating) as average_rating,
                COUNT(*) as review_count
            FROM reviews
            WHERE status = 'approved'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date
        ");

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// API Endpoint Handler
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $collector = new AnalyticsCollector($conn);
        $stats = $collector->collectRatingStats();

        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
