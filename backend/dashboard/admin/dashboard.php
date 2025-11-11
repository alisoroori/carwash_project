@<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

// Check admin access using auth_check functions
requireRole('admin');

// Get current user info
$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CarWash</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="/carwash_project/frontend/js/admin/analytics/metrics-dashboard.js"></script>
    <link href="/carwash_project/frontend/css/admin/dashboard.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-white w-64 shadow-lg">
            <div class="p-4 border-b">
                <h1 class="text-xl font-bold">CarWash Admin</h1>
                <?php if ($currentUser): ?>
                <p class="text-sm text-gray-600 mt-2">
                    <i class="fas fa-user-shield mr-1"></i>
                    <?php echo htmlspecialchars($currentUser['full_name'] ?? $currentUser['user_name'] ?? 'Admin'); ?>
                </p>
                <?php endif; ?>
            </div>
                <nav class="p-4">
                <a href="#" class="block py-2 px-4 rounded hover:bg-blue-50 bg-blue-50 text-blue-600 font-medium mb-2" title="Genel Bakış" aria-label="Genel Bakış">
                    <i class="fas fa-chart-line mr-2"></i> Genel BakÄ±ÅŸ
                </a>
                <a href="reports.php" class="block py-2 px-4 rounded hover:bg-blue-50 mb-2" title="Raporlar" aria-label="Raporlar">
                    <i class="fas fa-file-alt mr-2"></i> Raporlar
                </a>
                <a href="analytics.php" class="block py-2 px-4 rounded hover:bg-blue-50 mb-2" title="Analizler" aria-label="Analizler">
                    <i class="fas fa-chart-bar mr-2"></i> Analizler
                </a>
                
                <div class="border-t mt-4 pt-4">
                    <a href="../../auth/logout.php" class="block py-2 px-4 rounded hover:bg-red-50 text-red-600">
                        <i class="fas fa-sign-out-alt mr-2"></i> Ã‡Ä±kÄ±ÅŸ Yap
                    </a>
                </div>
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
                        <i class="fas fa-arrow-up"></i> 12% geÃ§en aya gÃ¶re
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-1">Aktif Ä°ÅŸletmeler</div>
                    <div class="text-2xl font-bold" id="activeBusinesses">...</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-1">Toplam Randevu</div>
                    <div class="text-2xl font-bold" id="totalBookings">...</div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm text-gray-500 mb-1">Yeni Ãœyeler</div>
                    <div class="text-2xl font-bold" id="newUsers">...</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">AylÄ±k Gelir</h3>
                    <canvas id="revenueChart"></canvas>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">PopÃ¼ler Hizmetler</h3>
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
                                <th class="pb-3">Ä°ÅŸlem</th>
                                <th class="pb-3">KullanÄ±cÄ±</th>
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
</body>

</html>



