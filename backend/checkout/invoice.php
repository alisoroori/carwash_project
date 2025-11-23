<?php
require_once __DIR__ . '/../includes/bootstrap.php';

use App\Classes\Auth;
use App\Classes\Database;
use App\Classes\Logger;

Auth::requireRole(['customer']);
$db = Database::getInstance();

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<p>Rezervasyon bulunamadı.</p>"; exit;
}

// Check if this is a session-based reservation (fallback when DB insert failed)
$booking = null;
if (strpos($id, 's_') === 0) {
    // Load from session
    if (isset($_SESSION['reservations'][$id])) {
        $booking = $_SESSION['reservations'][$id];
        // Add session flag for later processing
        $booking['from_session'] = true;
    }
} else {
    // Load from database
    // Single comprehensive JOIN query to get all booking details
    $query = "
        SELECT
            b.*,
            u.full_name as customer_name,
            u.phone as customer_phone,
            u.email as customer_email,
            s.name as service_name,
            s.price as service_price,
            s.duration as service_duration,
            s.category as service_category,
            c.name as carwash_name,
            c.address as cw_address,
            c.city as cw_city,
            c.district as cw_district,
            c.phone as carwash_phone,
            c.email as carwash_email,
            c.latitude as carwash_lat,
            c.longitude as carwash_lng
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN services s ON b.service_id = s.id
        LEFT JOIN carwashes c ON b.carwash_id = c.id
        WHERE b.id = :id
    ";

    try {
        $booking = $db->fetchOne($query, ['id' => $id]);
    } catch (Exception $e) {
        Logger::info('Failed to fetch booking: ' . $e->getMessage());
        echo "<p>Bir hata oluştu. Lütfen daha sonra tekrar deneyin.</p>";
        exit;
    }

    // For database bookings, initialize vehicle data from bookings table and enhance with user_vehicles if possible
    if ($booking) {
        Logger::info('Fetched booking data: ' . json_encode($booking));

        // Initialize vehicle data from bookings table using CORRECT column names
        $booking['vehicle_brand'] = $booking['vehicle_type'] ?? ''; // vehicle_type is the brand/category
        $booking['vehicle_model'] = $booking['vehicle_model'] ?? '';
        $booking['vehicle_color'] = $booking['vehicle_color'] ?? '';
        $booking['vehicle_plate'] = $booking['vehicle_plate'] ?? '';

        // Try to enhance with complete vehicle data from user_vehicles if available
        // This provides richer data (actual brand name instead of enum value)
        if (!empty($booking['vehicle_type']) || !empty($booking['vehicle_plate'])) {
            $vehicleQuery = "SELECT brand, model, color, license_plate FROM user_vehicles WHERE user_id = :user_id AND (brand = :brand OR license_plate = :plate) LIMIT 1";
            try {
                $vehicleData = $db->fetchOne($vehicleQuery, [
                    'user_id' => $booking['user_id'],
                    'brand' => $booking['vehicle_type'] ?? '',
                    'plate' => $booking['vehicle_plate'] ?? ''
                ]);
                Logger::info('Vehicle query result: ' . json_encode($vehicleData));
                if ($vehicleData) {
                    // Override with complete data from user_vehicles if available
                    $booking['vehicle_brand'] = $vehicleData['brand'] ?: $booking['vehicle_brand'];
                    $booking['vehicle_model'] = $vehicleData['model'] ?: $booking['vehicle_model'];
                    $booking['vehicle_color'] = $vehicleData['color'] ?: $booking['vehicle_color'];
                    $booking['vehicle_plate'] = $vehicleData['license_plate'] ?: $booking['vehicle_plate'];
                }
            } catch (Exception $e) {
                Logger::info('Failed to fetch vehicle data from user_vehicles: ' . $e->getMessage());
            }
        }
    }
}

if (!$booking) {
    echo "<p>Rezervasyon bulunamadı.</p>"; exit;
}

// Process booking data with error handling
try {
    // If this is a session-based booking, normalize the data structure and fetch related info
    if (isset($booking['from_session']) && $booking['from_session']) {
        // Fetch user info
        $user = null;
        try {
            $user = $db->fetchOne("SELECT full_name, phone, email FROM users WHERE id = :user_id", ['user_id' => $booking['user_id']]);
        } catch (Exception $e) {
            // Log error and continue with session data
            Logger::info("Failed to fetch user data for session booking: " . $e->getMessage());
            $user = null;
        }
        
        // Fetch service info if service_id is numeric
        $service = null;
        if (is_numeric($booking['service_id'])) {
            try {
                $service = $db->fetchOne("SELECT name, price, duration, category FROM services WHERE id = :service_id", ['service_id' => $booking['service_id']]);
            } catch (Exception $e) {
                Logger::info("Failed to fetch service data for session booking: " . $e->getMessage());
                $service = null;
            }
        }
        
        // Fetch carwash info if location_id is numeric
        $carwash = null;
        if (is_numeric($booking['location_id'])) {
            try {
                $carwash = $db->fetchOne("SELECT name, address, city, district, phone, email, latitude, longitude FROM carwashes WHERE id = :carwash_id", ['carwash_id' => $booking['location_id']]);
            } catch (Exception $e) {
                Logger::info("Failed to fetch carwash data for session booking: " . $e->getMessage());
                $carwash = null;
            }
        }
        
        // Fetch vehicle info from user_vehicles if vehicle is numeric (ID)
        $vehicleData = null;
        if (is_numeric($booking['vehicle'])) {
            try {
                $vehicleData = $db->fetchOne("SELECT brand, model, color, license_plate FROM user_vehicles WHERE id = :vehicle_id AND user_id = :user_id", [
                    'vehicle_id' => (int)$booking['vehicle'],
                    'user_id' => $booking['user_id']
                ]);
            } catch (Exception $e) {
                Logger::info("Failed to fetch vehicle data for session booking: " . $e->getMessage());
                $vehicleData = null;
            }
        }
        
        // Normalize to match database booking structure
        $booking = [
            'id' => $id, // Use session id as booking id
            'user_id' => $booking['user_id'],
            'service_id' => $booking['service_id'],
            'booking_date' => $booking['date'],
            'booking_time' => $booking['time'],
            'vehicle_brand' => $vehicleData ? ($vehicleData['brand'] ?? '') : $booking['vehicle'], // Use brand from user_vehicles or fallback to vehicle string
            'vehicle_plate' => $vehicleData ? ($vehicleData['license_plate'] ?? '') : '', // Use license_plate from user_vehicles
            'vehicle_model' => $vehicleData ? ($vehicleData['model'] ?? '') : '', // Use model from user_vehicles
            'vehicle_color' => $vehicleData ? ($vehicleData['color'] ?? '') : '', // Use color from user_vehicles
            'status' => $booking['status'],
            'total_price' => $booking['price'],
            'notes' => $booking['notes'],
            'created_at' => $booking['created_at'],
            'updated_at' => $booking['created_at'],
            // Joined data
            'customer_name' => $user ? ($user['full_name'] ?? '') : '',
            'customer_phone' => $user ? ($user['phone'] ?? '') : '',
            'customer_email' => $user ? ($user['email'] ?? '') : '',
            'service_name' => $service ? ($service['name'] ?? '') : '',
            'service_price' => $service ? ($service['price'] ?? $booking['price']) : $booking['price'],
            'service_duration' => $service ? ($service['duration'] ?? 0) : 0,
            'service_category' => $service ? ($service['category'] ?? '') : '',
            'carwash_name' => $carwash ? ($carwash['name'] ?? $booking['location']) : $booking['location'],
            'cw_address' => $carwash ? ($carwash['address'] ?? '') : '',
            'cw_city' => $carwash ? ($carwash['city'] ?? '') : '',
            'cw_district' => $carwash ? ($carwash['district'] ?? '') : '',
            'carwash_phone' => $carwash ? ($carwash['phone'] ?? '') : '',
            'carwash_email' => $carwash ? ($carwash['email'] ?? '') : '',
            'carwash_lat' => $carwash ? ($carwash['latitude'] ?? null) : null,
            'carwash_lng' => $carwash ? ($carwash['longitude'] ?? null) : null,
        ];
    }

    // Handle package bookings (multiple services)
    $packageServices = [];
    if (!empty($booking['service_id']) && is_numeric($booking['service_id'])) {
        // Check if this is a package booking with multiple services
        try {
            $packageQuery = "
                SELECT
                    bs.*,
                    s.name as service_name,
                    s.price as original_price,
                    s.duration as service_duration,
                    s.category as service_category
                FROM booking_services bs
                LEFT JOIN services s ON bs.service_id = s.id
                WHERE bs.booking_id = :booking_id
                ORDER BY s.category DESC, s.price DESC
            ";
            $packageServices = $db->fetchAll($packageQuery, ['booking_id' => $booking['id']]);
        } catch (Exception $e) {
            Logger::info("Failed to fetch package services for booking: " . $e->getMessage());
            $packageServices = [];
        }
    }

    // Format booking data for display
    $bookingData = [
        'id' => $booking['id'],
        'booking_number' => $booking['booking_number'] ?? 'BK-' . $booking['id'],
        'status' => $booking['status'] ?? 'pending',
        'created_at' => $booking['created_at'] ?? '',
        'updated_at' => $booking['updated_at'] ?? '',

        // Customer Info
        'customer' => [
            'name' => $booking['customer_name'] ?? '',
            'phone' => $booking['customer_phone'] ?? '',
            'email' => $booking['customer_email'] ?? ''
        ],

        // Vehicle Info (from database JOIN)
        'vehicle' => [
            'brand' => $booking['vehicle_brand'] ?? '',
            'model' => $booking['vehicle_model'] ?? '',
            'plate' => $booking['vehicle_plate'] ?? '',
            'color' => $booking['vehicle_color'] ?? '',
            'year' => '', // Not stored in bookings
            'type' => $booking['vehicle_brand'] ?? ''
        ],

        // Service/Package Info
        'service' => [
            'name' => $booking['service_name'] ?? '',
            'price' => (float)($booking['service_price'] ?? 0),
            'duration' => $booking['service_duration'] ?? 0,
            'category' => $booking['service_category'] ?? '',
            'is_package' => count($packageServices) > 1
        ],

        // Package Services (if applicable)
        'package_services' => $packageServices,

        // Booking Info
        'booking' => [
            'date' => $booking['booking_date'] ?? '',
            'time' => $booking['booking_time'] ?? '',
            'datetime_formatted' => (!empty($booking['booking_date']) && !empty($booking['booking_time']))
                ? date('Y-m-d H:i', strtotime($booking['booking_date'] . ' ' . $booking['booking_time']))
                : '',
            'notes' => $booking['notes'] ?? '',
            'cancellation_reason' => $booking['cancellation_reason'] ?? ''
        ],

        // Pricing
        'pricing' => [
            'service_price' => (float)($booking['service_price'] ?? 0),
            'total_price' => (float)($booking['total_price'] ?? 0),
            'discount_amount' => (float)($booking['discount_amount'] ?? 0),
            'final_total' => (float)($booking['total_price'] ?? 0)
        ],

        // Carwash/Location Info
        'carwash' => [
            'name' => $booking['carwash_name'] ?? '',
            'address' => $booking['cw_address'] ?? '',
            'city' => $booking['cw_city'] ?? '',
            'district' => $booking['cw_district'] ?? '',
            'phone' => $booking['carwash_phone'] ?? '',
            'email' => $booking['carwash_email'] ?? '',
            'full_address' => trim(($booking['cw_address'] ?? '') . ', ' . ($booking['cw_district'] ?? '') . ', ' . ($booking['cw_city'] ?? ''), ', '),
            'map_link' => (!empty($booking['carwash_lat']) && !empty($booking['carwash_lng']))
                ? "https://www.google.com/maps?q={$booking['carwash_lat']},{$booking['carwash_lng']}"
                : ''
        ]
    ];

    Logger::info('BookingData vehicle section: ' . json_encode($bookingData['vehicle']));

    // Calculate package total if multiple services
    if (count($packageServices) > 1) {
        $packageTotal = 0;
        foreach ($packageServices as $service) {
            $packageTotal += (float)($service['price'] ?? 0);
        }
        $bookingData['pricing']['service_price'] = $packageTotal;
        $bookingData['pricing']['final_total'] = $packageTotal - $bookingData['pricing']['discount_amount'];
    }

} catch (Exception $e) {
    Logger::info("Error processing booking data: " . $e->getMessage());
    echo "<p>Bir hata oluştu. Lütfen daha sonra tekrar deneyin.</p>";
    exit;
}

// Base URL for assets
$base = defined('BASE_URL') ? BASE_URL : (isset($base_url) ? $base_url : '/carwash_project');
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Fatura / Ödeme Onayı</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base); ?>/dist/output.css">
    <style>
        /* Print styles: only invoice content */
        @media print {
            body * { visibility: hidden; }
            #invoiceContent, #invoiceContent * { visibility: visible; }
            #invoiceContent { position: absolute; left: 0; top: 0; width: 100%; }
            .no-print { display: none !important; }
        }

        /* Updated styling for better UI */
        body {
            background-color: #f7f7f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .invoice-box {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            background: #fff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .section-header {
            background-color: #e3e3eb;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            border-left: 4px solid #4f46e5;
        }

        .section-header h3 {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
        }

        .section-content {
            background-color: #fafafa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .detail-value {
            font-size: 1rem;
            color: #111827;
            font-weight: 500;
        }

        .invoice-header {
            border-bottom: 1px solid #eef2f7;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .company-meta {
            text-align: right;
        }

        @media (max-width: 640px) {
            .company-meta {
                text-align: left;
                margin-top: 16px;
            }
        }
    </style>
</head>
<body class="p-4 sm:p-6">
    <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-4 no-print">
                <div class="flex items-center gap-4">
                    <?php
                        // Use carwash data from the booking
                        $cw_name = htmlspecialchars($bookingData['carwash']['name']);
                        $cw_address = htmlspecialchars($bookingData['carwash']['full_address']);
                        $cw_phone = htmlspecialchars($bookingData['carwash']['phone']);
                        $cw_email = htmlspecialchars($bookingData['carwash']['email']);
                    ?>
                    <div class="flex items-center gap-3">
                        <?php
                            // Choose a logo path robustly to avoid 404s in different installs.
                            $candidate1 = __DIR__ . '/../../frontend/images/logo.png';
                            $candidate2 = __DIR__ . '/../logo01.png';
                            $candidate3 = __DIR__ . '/../../frontend/assets/img/default-user.png';

                            if (file_exists($candidate1)) {
                                $logo_url = $base . '/frontend/images/logo.png';
                            } elseif (file_exists($candidate2)) {
                                $logo_url = $base . '/backend/logo01.png';
                            } elseif (file_exists($candidate3)) {
                                $logo_url = $base . '/frontend/assets/img/default-user.png';
                            } else {
                                // Last resort: use a data URI 1x1 transparent GIF to avoid broken image icon
                                $logo_url = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
                            }
                        ?>
                        <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Logo" class="w-20 h-20 object-contain">
                        <div>
                            <div class="text-lg font-bold text-gray-900"><?php echo $cw_name; ?></div>
                            <div class="text-sm text-gray-600"><?php echo $cw_address; ?></div>
                        </div>
                    </div>
                </div>

                <div class="company-meta text-sm text-gray-700">
                    <div><strong>Fatura No:</strong> <?php echo htmlspecialchars($bookingData['booking_number']); ?></div>
                    <div><strong>Tarih:</strong> <?php echo htmlspecialchars(date('d.m.Y')); ?></div>
                    <?php if ($cw_phone): ?><div><strong>Tel:</strong> <?php echo $cw_phone; ?></div><?php endif; ?>
                    <?php if ($cw_email): ?><div><strong>Email:</strong> <?php echo $cw_email; ?></div><?php endif; ?>
                </div>
            </div>        <div id="invoiceContent" class="invoice-box p-6">
            <div class="invoice-header flex flex-col sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-1">Rezervasyon Faturası</h2>
                    <div class="text-sm text-gray-600">Lütfen bilgilerinizi kontrol edin ve ödemeye devam edin.</div>
                </div>
                <div class="mt-4 sm:mt-0 text-sm text-gray-700">
                    <div><strong>Fatura No:</strong> <?php echo htmlspecialchars($bookingData['booking_number']); ?></div>
                    <div><strong>Rezervasyon ID:</strong> <?php echo htmlspecialchars($bookingData['id']); ?></div>
                    <div><strong>Oluşturulma:</strong> <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($bookingData['created_at']))); ?></div>
                    <div><strong>Durum:</strong>
                        <span class="px-2 py-1 text-xs rounded-full <?php
                            echo match($bookingData['status']) {
                                'confirmed' => 'bg-green-100 text-green-800',
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'completed' => 'bg-blue-100 text-blue-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                        ?>">
                            <?php echo htmlspecialchars(ucfirst($bookingData['status'])); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="mt-6">
                <div class="section-header">
                    <h3>Müşteri Bilgileri</h3>
                </div>
                <div class="section-content">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Ad Soyad</div>
                            <div class="detail-value"><?php echo htmlspecialchars($bookingData['customer']['name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Telefon</div>
                            <div class="detail-value"><?php echo htmlspecialchars($bookingData['customer']['phone'] ?: '-'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?php echo htmlspecialchars($bookingData['customer']['email'] ?: '-'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vehicle Information -->
            <div class="mt-6">
                <div class="section-header">
                    <h3>Araç Bilgileri</h3>
                </div>
                <div class="section-content">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Marka</div>
                            <div class="detail-value"><?php echo htmlspecialchars($bookingData['vehicle']['brand']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Model</div>
                            <div class="detail-value"><?php echo htmlspecialchars($bookingData['vehicle']['model']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Renk</div>
                            <div class="detail-value"><?php echo htmlspecialchars($bookingData['vehicle']['color']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Plaka</div>
                            <div class="detail-value"><?php echo htmlspecialchars($bookingData['vehicle']['plate']); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Information -->
            <div class="mt-6">
                <div class="section-header">
                    <h3>Hizmet Bilgileri</h3>
                </div>
                <div class="section-content">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Hizmet Adı</div>
                            <div class="detail-value"><?php echo htmlspecialchars($bookingData['service']['name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Hizmet Türü</div>
                            <div class="detail-value"><?php echo htmlspecialchars(ucfirst($bookingData['service']['category']) ?: '-'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Fiyat</div>
                            <div class="detail-value">₺<?php echo number_format($bookingData['pricing']['service_price'], 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservation Information -->
            <div class="mt-6">
                <div class="section-header">
                    <h3>Rezervasyon Bilgileri</h3>
                </div>
                <div class="section-content">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Tarih</div>
                            <div class="detail-value"><?php echo htmlspecialchars(date('d.m.Y', strtotime($bookingData['booking']['date']))); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Saat</div>
                            <div class="detail-value"><?php echo htmlspecialchars(date('H:i', strtotime($bookingData['booking']['time']))); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Oluşturulma Tarihi</div>
                            <div class="detail-value"><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($bookingData['created_at']))); ?></div>
                        </div>
                    </div>
                    <?php if (!empty($bookingData['booking']['notes'])): ?>
                    <div class="mt-4">
                        <div class="detail-label">Notlar</div>
                        <div class="detail-value mt-1 p-3 bg-white rounded border"><?php echo nl2br(htmlspecialchars($bookingData['booking']['notes'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Location Information -->
            <div class="mt-6">
                <div class="section-header">
                    <h3>Konum Bilgileri</h3>
                </div>
                <div class="section-content">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Adres</div>
                            <div class="detail-value"><?php echo htmlspecialchars($bookingData['carwash']['address']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">İlçe</div>
                            <div class="detail-value"><?php echo htmlspecialchars($bookingData['carwash']['district']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Şehir</div>
                            <div class="detail-value"><?php echo htmlspecialchars($bookingData['carwash']['city']); ?></div>
                        </div>
                    </div>
                    <?php if (!empty($bookingData['carwash']['phone'])): ?>
                    <div class="detail-item mt-4">
                        <div class="detail-label">Telefon</div>
                        <div class="detail-value"><?php echo htmlspecialchars($bookingData['carwash']['phone']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($bookingData['carwash']['email'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?php echo htmlspecialchars($bookingData['carwash']['email']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pricing Table -->
            <div class="mt-6">
                <div class="section-header">
                    <h3>Fiyatlandırma</h3>
                </div>
                <div class="section-content">
                    <div class="space-y-3">
                        <?php if ($bookingData['service']['is_package'] && !empty($bookingData['package_services'])): ?>
                            <!-- Package pricing breakdown -->
                            <?php foreach ($bookingData['package_services'] as $service): ?>
                            <div class="flex justify-between items-center p-3 bg-white rounded border">
                                <div class="font-medium"><?php echo htmlspecialchars($service['service_name']); ?></div>
                                <div class="font-medium">₺<?php echo number_format($service['price'], 2); ?></div>
                            </div>
                            <?php endforeach; ?>
                            <hr class="my-3">
                        <?php else: ?>
                            <!-- Single service pricing -->
                            <div class="flex justify-between items-center">
                                <span class="font-medium"><?php echo htmlspecialchars($bookingData['service']['name']); ?> (<?php echo htmlspecialchars($bookingData['service']['duration']); ?> dk)</span>
                                <span class="font-medium">₺<?php echo number_format($bookingData['pricing']['service_price'], 2); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($bookingData['pricing']['discount_amount'] > 0): ?>
                        <div class="flex justify-between items-center text-green-600">
                            <span>İndirim</span>
                            <span>-₺<?php echo number_format($bookingData['pricing']['discount_amount'], 2); ?></span>
                        </div>
                        <?php endif; ?>

                        <hr class="my-3 border-t-2">
                        <div class="flex justify-between items-center font-bold text-lg">
                            <span>Toplam</span>
                            <span>₺<?php echo number_format($bookingData['pricing']['final_total'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center justify-between gap-3 flex-col sm:flex-row no-print">
            <div class="flex gap-2">
                <a href="javascript:history.back()" class="px-4 py-2 border rounded bg-white">Geri</a>
            </div>

            <div class="flex gap-2">
                <button id="printBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded">Yazdır</button>
                <button id="pdfBtn" class="px-4 py-2 bg-blue-600 text-white rounded">PDF İndir</button>

                <form method="post" action="pay.php" class="inline">
                    <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($bookingData['id']); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Ödemeye Geç</button>
                </form>
            </div>
        </div>
    </div>

    <!-- html2pdf for client-side PDF generation (keeps styling) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
    <script>
        (function(){
            const printBtn = document.getElementById('printBtn');
            const pdfBtn = document.getElementById('pdfBtn');
            const invoice = document.getElementById('invoiceContent');

            if (printBtn) printBtn.addEventListener('click', function(){ window.print(); });

            if (pdfBtn && invoice) pdfBtn.addEventListener('click', function(){
                // Show loading state
                const originalText = pdfBtn.textContent;
                pdfBtn.textContent = 'PDF Oluşturuluyor...';
                pdfBtn.disabled = true;

                const opt = {
                    margin:       0.4,
                    filename:     'invoice-<?php echo preg_replace('/[^A-Za-z0-9_-]/','',htmlspecialchars($bookingData['id'])); ?>.pdf',
                    image:        { type: 'jpeg', quality: 0.85 }, // Reduced quality for faster processing
                    html2canvas:  { scale: 1.5, useCORS: true }, // Reduced scale for better performance
                    jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
                };

                // Use the original element instead of cloning for better performance
                html2pdf().set(opt).from(invoice).save().then(() => {
                    // Reset button state
                    pdfBtn.textContent = originalText;
                    pdfBtn.disabled = false;
                }).catch(err => {
                    console.error(err);
                    alert('PDF oluşturulamadı.');
                    pdfBtn.textContent = originalText;
                    pdfBtn.disabled = false;
                });
            });
        })();
    </script>
</body>
</html>
