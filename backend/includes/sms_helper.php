<?php
require_once 'db.php';
class SMSHelper
{
    private $api_key;
    private $sender_id;
    private $api_url;
    private $template_manager;

    public function __construct($db_connection)
    {
        // Initialize with your SMS gateway credentials
        $this->api_key = 'YOUR_API_KEY';
        $this->sender_id = 'CARWASH';
        $this->api_url = 'https://api.netgsm.com.tr/sms/send/get/';
        $this->template_manager = new SMSTemplateManager($db_connection);
    }

    public function sendBookingConfirmation($phone, $booking_data)
    {
        $message = $this->template_manager->renderTemplate('BOOKING_CONFIRMATION', [
            'booking_id' => $booking_data['booking_id'],
            'date' => $booking_data['booking_date'],
            'time' => $booking_data['booking_time'],
            'service' => $booking_data['service_type']
        ]);
        return $this->sendSMS($phone, $message);
    }

    public function sendBookingNotificationToCarwash($phone, $booking_data)
    {
        $message = $this->getCarwashNotificationMessage($booking_data);
        return $this->sendSMS($phone, $message);
    }

    private function sendSMS($phone, $message)
    {
        // Remove any non-numeric characters from phone
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Prepare API parameters
        $params = [
            'usercode' => $this->api_key,
            'password' => 'YOUR_API_PASSWORD',
            'gsmno' => $phone,
            'message' => $message,
            'msgheader' => $this->sender_id,
            'dil' => 'TR'
        ];

        // Make API request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_url . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        // Log SMS attempt
        $this->logSMSAttempt($phone, $message, $response);

        return $response;
    }

    private function getCarwashNotificationMessage($data)
    {
        return sprintf(
            "Yeni Rezervasyon\n" .
                "No: #%s\n" .
                "Müşteri: %s\n" .
                "Tarih: %s Saat: %s",
            $data['booking_id'],
            $data['customer_name'],
            $data['booking_date'],
            $data['booking_time']
        );
    }

    private function logSMSAttempt($phone, $message, $response)
    {
        global $conn;

        $stmt = $conn->prepare("
            INSERT INTO sms_logs (
                phone_number,
                message,
                response,
                created_at
            ) VALUES (?, ?, ?, NOW())
        ");

        $stmt->bind_param('sss', $phone, $message, $response);
        $stmt->execute();
    }
}
