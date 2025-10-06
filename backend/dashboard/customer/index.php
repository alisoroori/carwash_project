<?php
session_start();
require_once '../../includes/db.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../../auth/login.php');
    exit();
}

// Get customer information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'customer'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Paneli - AquaTR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <span class="text-xl font-semibold">
                        <i class="fas fa-user-circle text-blue-600"></i>
                        <?php echo htmlspecialchars($user['name']); ?>
                    </span>
                </div>
                <div>
                    <a href="../../auth/logout.php" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-sign-out-alt"></i> Çıkış
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Quick Actions -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Hızlı İşlemler</h2>
                <div class="space-y-4">
                    <a href="new_booking.php" class="block bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                        <i class="fas fa-calendar-plus"></i> Yeni Randevu
                    </a>
                    <a href="my_bookings.php" class="block bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700">
                        <i class="fas fa-calendar-check"></i> Randevularım
                    </a>
                </div>
            </div>

            <!-- Active Bookings -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Aktif Randevular</h2>
                <?php
                $stmt = $conn->prepare("
                    SELECT b.*, c.business_name, s.service_name 
                    FROM bookings b
                    JOIN carwashes c ON b.carwash_id = c.id
                    JOIN services s ON b.service_id = s.id
                    WHERE b.customer_id = ? AND b.status IN ('pending', 'confirmed')
                    ORDER BY b.booking_date ASC, b.booking_time ASC
                    LIMIT 3
                ");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $bookings = $stmt->get_result();

                if ($bookings->num_rows > 0) {
                    while ($booking = $bookings->fetch_assoc()) {
                        echo '<div class="border-b pb-3 mb-3 last:border-0">';
                        echo '<p class="font-semibold">' . htmlspecialchars($booking['business_name']) . '</p>';
                        echo '<p class="text-sm text-gray-600">' . htmlspecialchars($booking['service_name']) . '</p>';
                        echo '<p class="text-sm text-gray-500">';
                        echo date('d.m.Y', strtotime($booking['booking_date'])) . ' - ';
                        echo date('H:i', strtotime($booking['booking_time']));
                        echo '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<p class="text-gray-500">Aktif randevunuz bulunmamaktadır.</p>';
                }
                ?>
            </div>

            <!-- Profile Summary -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Profil Bilgileri</h2>
                <div class="space-y-2">
                    <p><i class="fas fa-envelope text-blue-600 w-6"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><i class="fas fa-phone text-blue-600 w-6"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                    <p><i class="fas fa-map-marker-alt text-blue-600 w-6"></i> <?php echo htmlspecialchars($user['address']); ?></p>
                </div>
                <a href="edit_profile.php" class="block mt-4 text-blue-600 hover:text-blue-800">
                    <i class="fas fa-edit"></i> Profili Düzenle
                </a>
            </div>
        </div>
    </div>
</body>

</html>