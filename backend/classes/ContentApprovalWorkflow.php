<?php
declare(strict_types=1);

namespace App\Classes;

class ContentApprovalWorkflow
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->ensureApprovalTable();
    }

    private function ensureApprovalTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS content_approvals (
            id INT PRIMARY KEY AUTO_INCREMENT,
            content_type VARCHAR(50) NOT NULL,
            content_id INT NOT NULL,
            content JSON NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            submitted_by INT NOT NULL,
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            reviewed_by INT NULL,
            reviewed_at DATETIME NULL,
            comments TEXT,
            FOREIGN KEY (submitted_by) REFERENCES users(id),
            FOREIGN KEY (reviewed_by) REFERENCES users(id),
            INDEX (content_type, content_id),
            INDEX (status)
        )";

        $this->conn->query($sql);
    }

    public function submitForApproval($contentType, $contentId, $content, $userId)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO content_approvals 
            (content_type, content_id, content, submitted_by)
            VALUES (?, ?, ?, ?)
        ");

        $contentJson = json_encode($content);
        $stmt->bind_param('sisi', $contentType, $contentId, $contentJson, $userId);
        return $stmt->execute();
    }

    public function approve($approvalId, $reviewerId, $comments = '')
    {
        return $this->updateApprovalStatus($approvalId, 'approved', $reviewerId, $comments);
    }

    public function reject($approvalId, $reviewerId, $comments)
    {
        return $this->updateApprovalStatus($approvalId, 'rejected', $reviewerId, $comments);
    }

    private function updateApprovalStatus($approvalId, $status, $reviewerId, $comments)
    {
        $stmt = $this->conn->prepare("
            UPDATE content_approvals
            SET status = ?, reviewed_by = ?, reviewed_at = NOW(), comments = ?
            WHERE id = ?
        ");

        $stmt->bind_param('sisi', $status, $reviewerId, $comments, $approvalId);
        return $stmt->execute();
    }
}

