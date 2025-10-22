<?php
declare(strict_types=1);

namespace App\Classes;

class ApprovalNotifier
{
    private $mailer;
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->initializeMailer();
    }

    private function initializeMailer()
    {
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = SMTP_HOST;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = SMTP_USER;
        $this->mailer->Password = SMTP_PASS;
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = SMTP_PORT;
        $this->mailer->setFrom(SMTP_FROM, 'CarWash Content System');
    }

    public function notifyReviewers($contentId, $contentType)
    {
        $stmt = $this->conn->prepare("
            SELECT u.email, u.name
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            WHERE ur.role = 'content_reviewer'
        ");

        $stmt->execute();
        $reviewers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($reviewers as $reviewer) {
            $this->sendReviewRequest($reviewer, $contentId, $contentType);
        }
    }

    public function notifySubmitter($contentId, $status, $comments)
    {
        $stmt = $this->conn->prepare("
            SELECT u.email, u.name, ca.content_type
            FROM content_approvals ca
            JOIN users u ON ca.submitted_by = u.id
            WHERE ca.id = ?
        ");

        $stmt->bind_param('i', $contentId);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        $this->sendStatusUpdate($data, $status, $comments);
    }

    private function sendReviewRequest($reviewer, $contentId, $contentType)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($reviewer['email'], $reviewer['name']);
            $this->mailer->Subject = "New Content Review Request";

            $body = $this->getReviewRequestTemplate([
                'reviewerName' => $reviewer['name'],
                'contentType' => $contentType,
                'contentId' => $contentId,
                'reviewUrl' => $this->getReviewUrl($contentId)
            ]);

            $this->mailer->Body = $body;
            $this->mailer->send();
        } catch (Exception $e) {
            error_log("Failed to send review request email: " . $e->getMessage());
        }
    }

    private function getReviewRequestTemplate($data)
    {
        // Load HTML template and replace placeholders
        $template = file_get_contents(__DIR__ . '/templates/review_request.html');
        foreach ($data as $key => $value) {
            $template = str_replace("{{" . $key . "}}", $value, $template);
        }
        return $template;
    }
}

