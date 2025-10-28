<?php
class ReviewManager
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function addReview($userId, $carwashId, $orderId, $rating, $comment)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO reviews (user_id, carwash_id, order_id, rating, comment)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param('iiiis', $userId, $carwashId, $orderId, $rating, $comment);
        return $stmt->execute();
    }

    public function getCarwashReviews($carwashId, $status = 'approved')
    {
        $stmt = $this->conn->prepare("
            SELECT r.*, u.name as user_name
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.carwash_id = ? AND r.status = ?
            ORDER BY r.created_at DESC
        ");

        $stmt->bind_param('is', $carwashId, $status);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAverageRating($carwashId)
    {
        $stmt = $this->conn->prepare("
            SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
            FROM reviews
            WHERE carwash_id = ? AND status = 'approved'
        ");

        $stmt->bind_param('i', $carwashId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getUserReviews($userId)
    {
        $stmt = $this->conn->prepare("
            SELECT r.*, c.name as carwash_name
            FROM reviews r
            JOIN carwash c ON r.carwash_id = c.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
        ");

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function canUserReview($userId, $carwashId)
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as review_count
            FROM reviews
            WHERE user_id = ? AND carwash_id = ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        $stmt->bind_param('ii', $userId, $carwashId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['review_count'] < 1;
    }
}
