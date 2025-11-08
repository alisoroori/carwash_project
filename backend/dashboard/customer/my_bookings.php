<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/bootstrap.php';

use App\Classes\Database;
use App\Classes\Session;
use App\Classes\Auth;

// Initialize session and require auth
Session::start();
Auth::requireAuth();

// Ensure the user is a customer
$userId = Session::get('user_id') ?? ($_SESSION['user_id'] ?? null);
if (empty($userId) || !Auth::hasRole('customer')) {
    header('Location: ../../auth/login.php');
    exit();
}

// Get customer's bookings (use PSR-4 Database)
$db = Database::getInstance();
$stmt = $db->prepare("SELECT b.*, c.business_name as business_name, c.contact_phone as profile_phone, s.name as service_name, s.price as service_price
    FROM bookings b
    LEFT JOIN carwash_profiles c ON b.carwash_id = c.id
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.user_id = :uid
    ORDER BY b.booking_date DESC, b.booking_time DESC");
$stmt->execute(['uid' => $userId]);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RandevularÄ±m - AquaTR</title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg mb-8">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="text-xl font-semibold text-blue-600">
                    <i class="fas fa-arrow-left"></i> Panele DÃ¶n
                </a>
                <a href="new_booking.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <i class="fas fa-plus"></i> Yeni Randevu
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">RandevularÄ±m</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['success']; ?></span>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <p class="text-gray-600">HenÃ¼z randevunuz bulunmamaktadÄ±r.</p>
                <a href="new_booking.php" class="inline-block mt-4 text-blue-600 hover:text-blue-800">
                    <i class="fas fa-plus"></i> Yeni Randevu OluÅŸtur
                </a>
            </div>
        <?php else: ?>
            <div class="grid gap-6">
                <?php foreach ($bookings as $booking): ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-800">
                                    <?php echo htmlspecialchars($booking['business_name']); ?>
                                </h2>
                                <p class="text-gray-600"><?php echo htmlspecialchars($booking['service_name']); ?></p>
                            </div>
                            <div class="text-right">
                                <span class="<?php echo getStatusClass($booking['status']); ?>">
                                    <?php echo getStatusText($booking['status']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Tarih</p>
                                <p class="font-medium">
                                    <?php echo date('d.m.Y', strtotime($booking['booking_date'])); ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Saat</p>
                                <p class="font-medium">
                                    <?php echo date('H:i', strtotime($booking['booking_time'])); ?>
                                </p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <p class="text-sm text-gray-600">Ãœcret</p>
                            <p class="font-medium"><?php echo number_format((float)($booking['total_amount'] ?? $booking['total_price'] ?? 0), 2); ?> TL</p>
                        </div>

                        <div class="mt-6 flex justify-between items-center">
                            <?php if (!empty($booking['profile_phone'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($booking['profile_phone']); ?>"
                                    class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-phone"></i> Ara
                                </a>
                            <?php endif; ?>

                            <?php if ($booking['status'] === 'pending'): ?>
                                <button onclick="cancelBooking(<?php echo $booking['id']; ?>)"
                                    class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-times"></i> Ä°ptal Et
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function cancelBooking(bookingId) {
            if (confirm('Randevuyu iptal etmek istediÄŸinizden emin misiniz?')) {
                fetch('cancel_booking.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `booking_id=${bookingId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.error || 'Bir hata oluÅŸtu.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Bir hata oluÅŸtu.');
                    });
            }
        }
    </script>

    <?php
    function getStatusClass($status)
    {
        switch ($status) {
            case 'pending':
                return 'px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm';
            case 'confirmed':
                return 'px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm';
            case 'completed':
                return 'px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm';
            case 'cancelled':
                return 'px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm';
            default:
                return 'px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm';
        }
    }

    function getStatusText($status)
    {
        switch ($status) {
            case 'pending':
                return 'Beklemede';
            case 'confirmed':
                return 'OnaylandÄ±';
            case 'completed':
                return 'TamamlandÄ±';
            case 'cancelled':
                return 'Ä°ptal Edildi';
            default:
                return 'Bilinmiyor';
        }
    }
    ?>
</body>

</html>

