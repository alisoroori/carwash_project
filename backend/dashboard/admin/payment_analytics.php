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
    <title>Payment Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Total Revenue</h3>
                <div class="mt-2 flex items-baseline">
                    <div class="text-2xl font-semibold" id="totalRevenue">₺0</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Success Rate</h3>
                <div class="mt-2 flex items-baseline">
                    <div class="text-2xl font-semibold" id="successRate">0%</div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Revenue Trends</h2>
                <canvas id="revenueTrendsChart"></canvas>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Payment Methods</h2>
                <canvas id="paymentMethodsChart"></canvas>
            </div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/admin/payment-analytics.js"></script>
</body>

</html>
