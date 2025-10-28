<?php
class EmailHelper
{
    private $from_email;
    private $from_name;

    public function __construct()
    {
        $this->from_email = 'noreply@carwash.com';
        $this->from_name = 'CarWash Rezervasyon';
    }

    public function sendBookingConfirmation($booking_data)
    {
        $subject = 'Rezervasyon Onayı - CarWash';

        $message = $this->getBookingEmailTemplate($booking_data);

        $headers = $this->getEmailHeaders();

        return mail(
            $booking_data['customer_email'],
            $subject,
            $message,
            $headers
        );
    }

    public function sendBookingNotificationToCarwash($booking_data)
    {
        $subject = 'Yeni Rezervasyon Bildirimi - CarWash';

        $message = $this->getCarwashNotificationTemplate($booking_data);

        $headers = $this->getEmailHeaders();

        return mail(
            $booking_data['carwash_email'],
            $subject,
            $message,
            $headers
        );
    }

    private function getEmailHeaders()
    {
        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->from_name . ' <' . $this->from_email . '>',
            'X-Mailer: PHP/' . phpversion()
        );

        return implode("\r\n", $headers);
    }

    private function getBookingEmailTemplate($data)
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #3B82F6; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .footer { text-align: center; padding: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Rezervasyon Onayı</h1>
                </div>
                <div class="content">
                    <p>Sayın ' . htmlspecialchars($data['customer_name']) . ',</p>
                    <p>Rezervasyonunuz başarıyla oluşturuldu. Detaylar aşağıdadır:</p>
                    <ul>
                        <li>Rezervasyon No: #' . $data['booking_id'] . '</li>
                        <li>Tarih: ' . $data['booking_date'] . '</li>
                        <li>Saat: ' . $data['booking_time'] . '</li>
                        <li>Hizmet: ' . $data['service_type'] . '</li>
                        <li>Fiyat: ' . $data['price'] . ' TL</li>
                    </ul>
                    <p>Rezervasyonunuzu görüntülemek veya değişiklik yapmak için hesabınıza giriş yapabilirsiniz.</p>
                </div>
                <div class="footer">
                    <p>Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayınız.</p>
                </div>
            </div>
        </body>
        </html>';
    }

    private function getCarwashNotificationTemplate($data)
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #3B82F6; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .footer { text-align: center; padding: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Yeni Rezervasyon</h1>
                </div>
                <div class="content">
                    <p>Yeni bir rezervasyon oluşturuldu:</p>
                    <ul>
                        <li>Rezervasyon No: #' . $data['booking_id'] . '</li>
                        <li>Müşteri: ' . htmlspecialchars($data['customer_name']) . '</li>
                        <li>Tarih: ' . $data['booking_date'] . '</li>
                        <li>Saat: ' . $data['booking_time'] . '</li>
                        <li>Hizmet: ' . $data['service_type'] . '</li>
                        <li>Araç Tipi: ' . $data['vehicle_type'] . '</li>
                    </ul>
                    <p>Detayları görmek için yönetim panelinize giriş yapabilirsiniz.</p>
                </div>
                <div class="footer">
                    <p>Bu e-posta otomatik olarak gönderilmiştir. Lütfen yanıtlamayınız.</p>
                </div>
            </div>
        </body>
        </html>';
    }
}
