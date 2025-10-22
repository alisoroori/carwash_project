<?php
declare(strict_types=1);

namespace App\Classes;

class ContentAnalytics
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->ensureAnalyticsTables();
    }

    private function ensureAnalyticsTables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS content_views (
            id INT PRIMARY KEY AUTO_INCREMENT,
            content_type VARCHAR(50) NOT NULL,
            content_id INT NOT NULL,
            user_id INT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            INDEX (content_type, content_id),
            INDEX (viewed_at)
        )";

        $this->conn->query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS content_interactions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            content_type VARCHAR(50) NOT NULL,
            content_id INT NOT NULL,
            user_id INT NULL,
            interaction_type VARCHAR(50) NOT NULL,
            interaction_data JSON,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            INDEX (content_type, content_id),
            INDEX (interaction_type)
        )";

        $this->conn->query($sql);
    }

    public function trackView($contentType, $contentId, $userId = null)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO content_views 
            (content_type, content_id, user_id, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");

        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $stmt->bind_param('siiss', $contentType, $contentId, $userId, $ipAddress, $userAgent);
        return $stmt->execute();
    }

    public function trackInteraction($contentType, $contentId, $interactionType, $data = [], $userId = null)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO content_interactions 
            (content_type, content_id, user_id, interaction_type, interaction_data)
            VALUES (?, ?, ?, ?, ?)
        ");

        $interactionData = json_encode($data);
        $stmt->bind_param('siiss', $contentType, $contentId, $userId, $interactionType, $interactionData);
        return $stmt->execute();
    }

    public function getContentStats($contentType, $contentId, $period = 30)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(DISTINCT cv.id) as view_count,
                COUNT(DISTINCT cv.user_id) as unique_viewers,
                COUNT(DISTINCT ci.id) as interaction_count
            FROM content_views cv
            LEFT JOIN content_interactions ci 
                ON cv.content_type = ci.content_type 
                AND cv.content_id = ci.content_id
            WHERE cv.content_type = ?
            AND cv.content_id = ?
            AND cv.viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");

        $stmt->bind_param('sii', $contentType, $contentId, $period);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

