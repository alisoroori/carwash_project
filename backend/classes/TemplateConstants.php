<?php
declare(strict_types=1);

namespace App\Classes;

class TemplateConstants {
    const TEMPLATES = [
        'BOOKING_CONFIRMATION' => 'Rezervasyon onayı bildirimi',
        'BOOKING_REMINDER' => 'Rezervasyon hatırlatma bildirimi',
        'BOOKING_CANCELLED' => 'Rezervasyon iptal bildirimi',
        'SERVICE_COMPLETED' => 'Hizmet tamamlanma bildirimi',
        'SPECIAL_OFFER' => 'Özel indirim kampanyası',
        'LOYALTY_PROGRAM' => 'Sadakat programı bildirimi',
        'MAINTENANCE_REMINDER' => 'Bakım hatırlatma bildirimi',
        'RAINY_DAY_OFFER' => 'Yağmurlu gün kampanyası',
        'BIRTHDAY_SPECIAL' => 'Doğum günü özel teklifi'
    ];

    /**
     * Get sample data for template testing
     */
    public static function getSampleData($templateCode) {
        $baseData = [
            'customer' => 'Ahmet Yılmaz',
            'booking_id' => '12345',
            'booking_url' => 'https://carwash.com/booking/',
            'carwash_name' => 'Premium CarWash'
        ];

        $specificData = [
            'BOOKING_REMINDER' => [
                'time' => '14:30'
            ],
            'BOOKING_CANCELLED' => [
                'refund_amount' => '150'
            ],
            'SERVICE_COMPLETED' => [
                'feedback_url' => 'https://carwash.com/feedback/12345'
            ],
            'SPECIAL_OFFER' => [
                'discount_amount' => '50',
                'coupon_code' => 'SPRING50',
                'expiry_date' => date('d.m.Y', strtotime('+7 days'))
            ],
            'LOYALTY_PROGRAM' => [
                'points' => '100',
                'total_points' => '450',
                'rewards_url' => 'https://carwash.com/rewards'
            ],
            'MAINTENANCE_REMINDER' => [
                'days' => '30'
            ],
            'RAINY_DAY_OFFER' => [
                'discount_percent' => '25',
                'promo_code' => 'RAIN25'
            ],
            'BIRTHDAY_SPECIAL' => [
                'discount_percent' => '30',
                'birthday_code' => 'HBD30',
                'valid_date' => date('d.m.Y', strtotime('+7 days'))
            ]
        ];

        return array_merge(
            $baseData, 
            isset($specificData[$templateCode]) ? $specificData[$templateCode] : []
        );
    }
}

// JavaScript function to load sample data
?>
<script>
function loadSampleData(templateCode) {
    fetch(`get_sample_data.php?template=${templateCode}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fill form inputs with sample data
                Object.keys(data.sample_data).forEach(key => {
                    const input = document.querySelector(`input[name="${key}"]`);
                    if (input) {
                        input.value = data.sample_data[key];
                    }
                });
                // Trigger preview
                previewTemplate();
            }
        });
}
</script>

<!-- Add a "Load Sample Data" button next to the preview button -->
<button type="button" onclick="loadSampleData(currentTemplate.code)"
        class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
    Örnek Veri Yükle
</button>
