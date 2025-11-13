<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

// Check user access
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HesabÄ±m - CarWash</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Profile Section -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center">
                <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-2xl">
                    <i class="fas fa-user"></i>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold" id="userName">...</h1>
                    <p class="text-gray-500" id="userEmail">...</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <a href="new_appointment.php" class="bg-white rounded-lg shadow p-6 hover:bg-blue-50">
                <i class="fas fa-calendar-plus text-blue-600 text-2xl mb-2"></i>
                <h3 class="font-semibold">Yeni Randevu</h3>
            </a>
            <a href="appointments.php" class="bg-white rounded-lg shadow p-6 hover:bg-blue-50">
                <i class="fas fa-clock text-blue-600 text-2xl mb-2"></i>
                <h3 class="font-semibold">RandevularÄ±m</h3>
            </a>
            <a href="favorites.php" class="bg-white rounded-lg shadow p-6 hover:bg-blue-50">
                <i class="fas fa-heart text-blue-600 text-2xl mb-2"></i>
                <h3 class="font-semibold">Favorilerim</h3>
            </a>
        </div>

        <!-- Recent Appointments -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold">Son RandevularÄ±m</h2>
            </div>
            <div class="p-6">
                <div id="recentAppointments">
                    <!-- Appointments will be loaded here -->
                </div>
            </div>
        </div>

        <!-- My Reviews -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-lg font-semibold">DeÄŸerlendirmelerim</h2>
            </div>
            <div class="p-6">
                <div id="userReviews">
                    <!-- Reviews will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="../../../frontend/js/user/dashboard.js"></script>
</body>

</html>



