<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

// Check carwash owner access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'carwash') {
    header('Location: ../../auth/login.php');
    exit();
}

$carwash_id = $_SESSION['carwash_id'];
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carwash Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-white w-64 shadow-lg">
            <div class="p-4 border-b">
                <h1 class="text-xl font-bold">İşletme Paneli</h1>
            </div>
            <nav class="p-4">
                <a href="#" class="block py-2 px-4 rounded hover:bg-blue-50 active">
                    <i class="fas fa-home mr-2"></i> Ana Sayfa
                </a>
                <a href="appointments.php" class="block py-2 px-4 rounded hover:bg-blue-50">
                    <i class="fas fa-calendar mr-2"></i> Randevular
                </a>
                <a href="services.php" class="block py-2 px-4 rounded hover:bg-blue-50">
                    <i class="fas fa-cog mr-2"></i> Hizmetler
                </a>
                <a href="reviews.php" class="block py-2 px-4 rounded hover:bg-blue-50">
                    <i class="fas fa-star mr-2"></i> Değerlendirmeler
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Today's Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-1">Bugünkü Randevular</div>
                    <div class="text-2xl font-bold" id="todayAppointments">...</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-1">Günlük Gelir</div>
                    <div class="text-2xl font-bold" id="todayRevenue">...</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-1">Ortalama Puan</div>
                    <div class="text-2xl font-bold" id="averageRating">...</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-1">Bu Ay</div>
                    <div class="text-2xl font-bold" id="monthlyRevenue">...</div>
                </div>
            </div>

            <!-- Upcoming Appointments -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold">Yaklaşan Randevular</h3>
                </div>
                <div class="p-6">
                    <table class="w-full" id="appointmentsTable">
                        <thead class="text-left text-sm text-gray-500">
                            <tr>
                                <th class="pb-3">Saat</th>
                                <th class="pb-3">Müşteri</th>
                                <th class="pb-3">Hizmet</th>
                                <th class="pb-3">Durum</th>
                                <th class="pb-3">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Appointments will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Haftalık Gelir</h3>
                    <canvas id="weeklyRevenueChart"></canvas>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Popüler Hizmetler</h3>
                    <canvas id="popularServicesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="../../../frontend/js/carwash/dashboard.js"></script>
</body>

</html>
