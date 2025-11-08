<?php
session_start();
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /carwash_project/frontend/auth/login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Pricing Analytics</title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/tailwind.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Price Impact Analysis -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Price Impact on Bookings</h2>
                <canvas id="priceImpactChart"></canvas>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Revenue by Time Slot</h2>
                <canvas id="revenueByTimeChart"></canvas>
            </div>
        </div>

        <!-- Demand Heatmap -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-lg font-semibold mb-4">Weekly Demand Heatmap</h2>
            <div id="demandHeatmap" class="h-64"></div>
        </div>

        <!-- Price Recommendations -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Price Recommendations</h2>
            <div id="priceRecommendations" class="space-y-4">
                <!-- Recommendations will be loaded dynamically -->
            </div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/admin/pricing-analytics.js"></script>
</body>

</html>

