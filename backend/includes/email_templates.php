<?php
class PaymentEmailTemplates
{
    private $templates = [
        'payment_success' => [
            'subject' => 'Payment Successful - CarWash Booking Confirmed',
            'template' => <<<HTML
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2>Payment Successful!</h2>
                <p>Dear {{customer_name}},</p>
                <p>Your payment of ₺{{amount}} has been successfully processed.</p>
                <div style="background: #f5f5f5; padding: 15px; margin: 20px 0;">
                    <h3>Booking Details:</h3>
                    <p>Booking ID: {{booking_id}}</p>
                    <p>Date: {{booking_date}}</p>
                    <p>Time: {{booking_time}}</p>
                    <p>CarWash: {{carwash_name}}</p>
                </div>
                <p><a href="{{receipt_url}}" style="color: #4A90E2;">View Receipt</a></p>
            </div>
            HTML
        ],
        'payment_failed' => [
            'subject' => 'Payment Failed - CarWash Booking',
            'template' => <<<HTML
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2>Payment Failed</h2>
                <p>Dear {{customer_name}},</p>
                <p>We were unable to process your payment of ₺{{amount}}.</p>
                <p>Reason: {{failure_reason}}</p>
                <p><a href="{{retry_url}}" style="background: #4A90E2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Try Again</a></p>
            </div>
            HTML
        ]
    ];

    public function getTemplate($type, $data)
    {
        if (!isset($this->templates[$type])) {
            throw new Exception('Template not found');
        }

        $template = $this->templates[$type]['template'];
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return [
            'subject' => $this->templates[$type]['subject'],
            'body' => $template
        ];
    }
}
