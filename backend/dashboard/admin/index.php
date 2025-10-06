<?php
session_start();
require_once '../../includes/db.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}

// Get statistics
$stats = [
    'customers' => $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'customer'")->fetch_assoc()['count'],
    'carwashes' => $conn->query("SELECT COUNT(*) as count FROM carwashes WHERE status = 'active'")->fetch_assoc()['count'],
    'bookings_today' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(booking_date) = CURDATE()")->fetch_assoc()['count'],
    'revenue_month' => $conn->query("SELECT COALESCE(SUM(total_price), 0) as total FROM bookings WHERE MONTH(booking_date) = MONTH(CURDATE()) AND status = 'completed'")->fetch_assoc()['total']
];

// Get recent bookings
$recent_bookings = $conn->query("
    SELECT b.*, u.name as customer_name, c.business_name, s.service_name
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    JOIN carwashes c ON b.carwash_id = c.id
    JOIN services s ON b.service_id = s.id
    ORDER BY b.created_at DESC
    LIMIT 5
");

// Get pending carwash registrations
$pending_carwashes = $conn->query("
    SELECT c.*, u.name as owner_name, u.email
    FROM carwashes c
    JOIN users u ON c.owner_id = u.id
    WHERE c.status = 'pending'
    ORDER BY c.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AquaTR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <h1 class="text-xl font-bold text-gray-800">
                    AquaTR Admin Panel
                </h1>
                <div class="flex items-center space-x-4">
                    <a href="settings.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-cog"></i> Ayarlar
                    </a>
                    <a href="../../auth/logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt"></i> Çıkış
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Toplam Müşteri</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['customers']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-car fa-2x"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Aktif Carwash</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['carwashes']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-calendar-check fa-2x"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Bugünkü Randevu</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $stats['bookings_today']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-money-bill fa-2x"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Aylık Gelir</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['revenue_month'], 2); ?> TL</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Bookings -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Son Randevular</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Müşteri</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Carwash</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($booking['customer_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($booking['business_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="<?php echo getStatusClass($booking['status']); ?>">
                                            <?php echo getStatusText($booking['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Carwash Registrations -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Onay Bekleyen Carwash'lar</h2>
                <?php if ($pending_carwashes->num_rows === 0): ?>
                    <p class="text-gray-500 text-center py-4">Onay bekleyen carwash bulunmamaktadır.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php while ($carwash = $pending_carwashes->fetch_assoc()): ?>
                            <div class="border rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold"><?php echo htmlspecialchars($carwash['business_name']); ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($carwash['owner_name']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($carwash['email']); ?></p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="approveCarwash(<?php echo $carwash['id']; ?>)"
                                            class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm hover:bg-green-200">
                                            Onayla
                                        </button>
                                        <button onclick="rejectCarwash(<?php echo $carwash['id']; ?>)"
                                            class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm hover:bg-red-200">
                                            Reddet
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function approveCarwash(id) {
            if (confirm('Bu carwash\'ı onaylamak istediğinize emin misiniz?')) {
                updateCarwashStatus(id, 'approve');
            }
        }

        function rejectCarwash(id) {
            if (confirm('Bu carwash\'ı reddetmek istediğinize emin misiniz?')) {
                updateCarwashStatus(id, 'reject');
            }
        }

        function updateCarwashStatus(id, action) {
            fetch('update_carwash_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `carwash_id=${id}&action=${action}`
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