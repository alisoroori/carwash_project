<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

// Check admin access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CarWash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-white w-64 shadow-lg">
            <div class="p-4 border-b">
                <h1 class="text-xl font-bold">CarWash Admin</h1>
            </div>
            <nav class="p-4">
                <a href="#" class="block py-2 px-4 rounded hover:bg-blue-50 active">
                    <i class="fas fa-chart-line mr-2"></i> Genel Bakış
                </a>
                <a href="reports.php" class="block py-2 px-4 rounded hover:bg-blue-50">
                    <i class="fas fa-file-alt mr-2"></i> Raporlar
                </a>
                <a href="analytics.php" class="block py-2 px-4 rounded hover:bg-blue-50">
                    <i class="fas fa-chart-bar mr-2"></i> Analizler
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-1">Toplam Ciro</div>
                    <div class="text-2xl font-bold" id="totalRevenue">...</div>
                    <div class="text-xs text-green-500 mt-2">
                        <i class="fas fa-arrow-up"></i> 12% geçen aya göre
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-1">Aktif İşletmeler</div>
                    <div class="text-2xl font-bold" id="activeBusinesses">...</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-1">Toplam Randevu</div>
                    <div class="text-2xl font-bold" id="totalBookings">...</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-1">Yeni Üyeler</div>
                    <div class="text-2xl font-bold" id="newUsers">...</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Aylık Gelir</h3>
                    <canvas id="revenueChart"></canvas>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Popüler Hizmetler</h3>
                    <canvas id="servicesChart"></canvas>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold">Son Aktiviteler</h3>
                </div>
                <div class="p-6">
                    <table class="w-full" id="activityTable">
                        <thead class="text-left text-sm text-gray-500">
                            <tr>
                                <th class="pb-3">Tarih</th>
                                <th class="pb-3">İşlem</th>
                                <th class="pb-3">Kullanıcı</th>
                                <th class="pb-3">Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Activities will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/admin/dashboard.js"></script>
</body>

</html>