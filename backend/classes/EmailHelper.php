<?php
declare(strict_types=1);

namespace App\Classes;

class EmailHelper
{
    private string $from_email;
    private string $from_name;

    public function __construct()
    {
        $this->from_email = 'noreply@carwash.com';
        $this->from_name  = 'CarWash Rezervasyon';
    }

    /**
     * Send booking confirmation email.
     *
     * @param array $booking_data
     * @return bool true on success, false on failure
     */
    public function sendBookingConfirmation(array $booking_data): bool
    {
        $subject = 'Rezervasyon Onayı - CarWash';
        $message = $this->getBookingEmailTemplate($booking_data);
        $headers = $this->getEmailHeaders();

        $to = (string)($booking_data['email'] ?? '');
        if ($to === '') {
            error_log('EmailHelper: missing recipient email in booking_data');
            return false;
        }

        // Use @ to suppress PHP warning; log failure if mail returns false
        $sent = @mail($to, $subject, $message, $headers);
        if (!$sent) {
            error_log('EmailHelper: mail() failed for recipient ' . $to);
        }

        return (bool)$sent;
    }

    /**
     * Build a simple booking email template.
     * Stubbed to avoid undefined method errors in editors.
     *
     * @param array $booking_data
     * @return string
     */
    private function getBookingEmailTemplate(array $booking_data): string
    {
        $customer = htmlspecialchars((string)($booking_data['customer_name'] ?? 'Müşteri'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $date     = htmlspecialchars((string)($booking_data['date'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $time     = htmlspecialchars((string)($booking_data['time'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $body  = "Merhaba {$customer},\r\n\r\n";
        $body .= "Rezervasyonunuz onaylandı.\r\n";
        if ($date !== '') {
            $body .= "Tarih: {$date}\r\n";
        }
        if ($time !== '') {
            $body .= "Saat: {$time}\r\n";
        }
        $body .= "\r\nTeşekkürler,\r\nCarWash";

        return $body;
    }

    /**
     * Return standard email headers for plain text UTF-8 emails.
     *
     * @return string
     */
    private function getEmailHeaders(): string
    {
        $from = "{$this->from_name} <{$this->from_email}>";
        $headers  = "From: {$from}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        return $headers;
    }
}

