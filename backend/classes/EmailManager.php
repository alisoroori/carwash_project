<?php
declare(strict_types=1);

namespace App\Classes;

class EmailManager
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->setupMailer();
    }

    private function setupMailer()
    {
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = getenv('SMTP_USERNAME');
        $this->mailer->Password = getenv('SMTP_PASSWORD');
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
    }

    public function sendBookingConfirmation($bookingId)
    {
        $booking = $this->getBookingDetails($bookingId);

        $this->mailer->setFrom('noreply@carwash.com', 'CarWash System');
        $this->mailer->addAddress($booking['email']);
        $this->mailer->Subject = 'Booking Confirmation - ' . $booking['carwash_name'];

        $body = $this->getEmailTemplate('booking_confirmation', [
            'customer_name' => $booking['customer_name'],
            'carwash_name' => $booking['carwash_name'],
            'date' => $booking['date'],
            'time' => $booking['time'],
            'services' => $booking['services'],
            'total' => $booking['total']
        ]);

        $this->mailer->Body = $body;
        $this->mailer->send();
    }
}

