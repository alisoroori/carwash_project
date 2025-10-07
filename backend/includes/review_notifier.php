<?php
class ReviewNotifier
{
    private $conn;
    private $mailer;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initializeMailer();
    }

    public function notifyNewReview($reviewId)
    {
        $review = $this->getReviewDetails($reviewId);

        // Notify CarWash Owner
        $this->notifyCarWash($review);

        // Notify Admin
        if ($review['rating'] <= 2) {
            $this->notifyAdmin($review);
        }

        // Store notification in database
        $this->storeNotification($review);
    }

    private function getReviewDetails($reviewId)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                r.*,
                u.name as customer_name,
                c.name as carwash_name,
                c.email as carwash_email
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            JOIN carwash c ON r.carwash_id = c.id
            WHERE r.id = ?
        ");

        $stmt->bind_param('i', $reviewId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function storeNotification($review)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications 
            (user_id, type, title, message, related_id)
            VALUES (?, 'review', ?, ?, ?)
        ");

        // For CarWash owner
        $title = "New Review Received";
        $message = "{$review['customer_name']} left a {$review['rating']}-star review";
        $stmt->bind_param(
            'issi',
            $review['carwash_owner_id'],
            $title,
            $message,
            $review['id']
        );
        $stmt->execute();
    }
}
