<?php
class ReportManager
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function submitReport($reviewId, $userId, $reason, $description)
    {
        // Check if user already reported this review
        $stmt = $this->conn->prepare("
            SELECT id FROM review_reports 
            WHERE review_id = ? AND user_id = ?
        ");
        $stmt->bind_param('ii', $reviewId, $userId);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Bu değerlendirme için zaten bir bildirim gönderdiniz');
        }

        // Submit new report
        $stmt = $this->conn->prepare("
            INSERT INTO review_reports (review_id, user_id, reason, description)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param('iiss', $reviewId, $userId, $reason, $description);
        return $stmt->execute();
    }

    public function getReportsCount($reviewId)
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM review_reports 
            WHERE review_id = ? AND status = 'pending'
        ");

        $stmt->bind_param('i', $reviewId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }
}
