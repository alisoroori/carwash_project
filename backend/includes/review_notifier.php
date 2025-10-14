<?php
class ReviewNotifier
{
    private $conn;
    private $mailer;

    public function __construct($conn)
    {
        $this->conn = $conn;
        // Initialize mailer if needed, e.g. $this->mailer = new PHPMailer();
        // For now, just set mailer to null to avoid undefined method error.
        $this->mailer = null;
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

    private function notifyCarWash($review)
    {
        // Example: send email to carwash owner
        $to = $review['carwash_email'];
        $subject = "New Review Received for {$review['carwash_name']}";
        $message = "Hello,\n\nYou have received a new review from {$review['customer_name']} with a rating of {$review['rating']} stars.\n\nReview: {$review['comment']}";
        $headers = "From: noreply@carwash.local";

        // Use PHP's mail function for demonstration (replace with PHPMailer if needed)
        mail($to, $subject, $message, $headers);
    }

    private function notifyAdmin($review)
    {
        // Example: send email to admin when a low rating is received
        $adminEmail = "admin@carwash.local";
        $subject = "Low Rating Alert for {$review['carwash_name']}";
        $message = "Attention Admin,\n\nA low rating ({$review['rating']} stars) was received for {$review['carwash_name']} from {$review['customer_name']}.\n\nReview: {$review['comment']}";
        $headers = "From: noreply@carwash.local";

        // Use PHP's mail function for demonstration (replace with PHPMailer if needed)
        mail($adminEmail, $subject, $message, $headers);
    }
}
