<?php
session_start();
require_once '../../includes/db.php';

// Check if user is logged in and is a carwash owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'carwash') {
    header('Location: ../../auth/login.php');
    exit();
}

// Get carwash details
$stmt = $conn->prepare("
    SELECT c.*, u.name as owner_name, u.email, u.phone
    FROM carwashes c
    JOIN users u ON c.owner_id = u.id
    WHERE c.owner_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$carwash = $stmt->get_result()->fetch_assoc();

// Get today's bookings
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT b.*, u.name as customer_name, u.phone as customer_phone, s.service_name
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    JOIN services s ON b.service_id = s.id
    WHERE b.carwash_id = ? AND b.booking_date = ?
    ORDER BY b.booking_time ASC
");
$stmt->bind_param("is", $carwash['id'], $today);
$stmt->execute();
$todays_bookings = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Araç Yıkama Paneli - AquaTR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800">
                        <?php echo htmlspecialchars($carwash['business_name']); ?>
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="profile.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-user"></i> Profil
                    </a>
                    <a href="../../auth/logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt"></i> Çıkış
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Bugünkü Randevular</h3>
                <p class="text-3xl font-bold text-blue-600">
                    <?php echo $todays_bookings->num_rows; ?>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Aktif Hizmetler</h3>
                <?php
                $active_services = $conn->query("SELECT COUNT(*) as count FROM services WHERE carwash_id = {$carwash['id']} AND status = 'active'")->fetch_assoc();
                ?>
                <p class="text-3xl font-bold text-green-600"><?php echo $active_services['count']; ?></p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Değerlendirme</h3>
                <p class="text-3xl font-bold text-yellow-600">
                    <?php echo number_format($carwash['rating'], 1); ?> ⭐
                </p>
            </div>
        </div>

        <!-- Today's Bookings -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Bugünkü Randevular</h2>
                <a href="all_bookings.php" class="text-blue-600 hover:text-blue-800">
                    Tüm Randevular <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>

            <?php if ($todays_bookings->num_rows === 0): ?>
                <p class="text-gray-500 text-center py-4">Bugün için randevu bulunmamaktadır.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Saat</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Müşteri</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Hizmet</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($booking = $todays_bookings->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo date('H:i', strtotime($booking['booking_time'])); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($booking['customer_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($booking['customer_phone']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($booking['service_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo number_format($booking['total_price'], 2); ?> TL
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="<?php echo getStatusClass($booking['status']); ?>">
                                            <?php echo getStatusText($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button onclick="updateStatus(<?php echo $booking['id']; ?>)"
                                            class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-sync-alt"></i> Güncelle
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Hızlı İşlemler</h2>
                <div class="space-y-4">
                    <a href="services.php" class="block bg-blue-600 text-white text-center py-2 px-4 rounded hover:bg-blue-700">
                        <i class="fas fa-cog"></i> Hizmetleri Yönet
                    </a>
                    <a href="working_hours.php" class="block bg-green-600 text-white text-center py-2 px-4 rounded hover:bg-green-700">
                        <i class="fas fa-clock"></i> Çalışma Saatleri
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Son Değerlendirmeler</h2>
                <?php
                $reviews = $conn->query("
                    SELECT r.*, u.name as customer_name
                    FROM reviews r
                    JOIN users u ON r.customer_id = u.id
                    WHERE r.carwash_id = {$carwash['id']}
                    ORDER BY r.created_at DESC
                    LIMIT 3
                ");

                if ($reviews->num_rows === 0): ?>
                    <p class="text-gray-500">Henüz değerlendirme yapılmamış.</p>
                    <?php else:
                    while ($review = $reviews->fetch_assoc()): ?>
                        <div class="border-b last:border-0 pb-3 mb-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($review['customer_name']); ?></p>
                                    <p class="text-yellow-500">
                                        <?php echo str_repeat('⭐', $review['rating']); ?>
                                    </p>
                                </div>
                                <span class="text-sm text-gray-500">
                                    <?php echo date('d.m.Y', strtotime($review['created_at'])); ?>
                                </span>
                            </div>
                            <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($review['comment']); ?></p>
                        </div>
                <?php endwhile;
                endif; ?>
            </div>
        </div>
    </div>

    <script>
        function updateStatus(bookingId) {
            const newStatus = prompt('Yeni durumu giriniz (confirmed/completed/cancelled):');
            if (!newStatus) return;

            fetch('update_booking_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `booking_id=${bookingId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Bir hata oluştu.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Bir hata oluştu.');
                });
        }
    </script>

    <?php
    function getStatusClass($status)
    {
        switch ($status) {
            case 'pending':
                return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800';
            case 'confirmed':
                return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800';
            case 'completed':
                return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800';
            case 'cancelled':
                return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800';
            default:
                return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800';
        }
    }

    function getStatusText($status)
    {
        switch ($status) {
            case 'pending':
                return 'Beklemede';
            case 'confirmed':
                return 'Onaylandı';
            case 'completed':
                return 'Tamamlandı';
            case 'cancelled':
                return 'İptal Edildi';
            default:
                return 'Bilinmiyor';
        }
    }
    ?>
</body>

</html>