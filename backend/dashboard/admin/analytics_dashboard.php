<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../auth/login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Real-time Analytics</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Real-time Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Active Users</h3>
                <div class="mt-2 flex items-baseline">
                    <div class="text-2xl font-semibold" id="activeUsers">0</div>
                    <div class="ml-2 text-sm text-gray-500">now</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Today's Bookings</h3>
                <div class="mt-2 flex items-baseline">
                    <div class="text-2xl font-semibold" id="todayBookings">0</div>
                    <div class="ml-2 text-sm text-green-500" id="bookingsTrend">+0%</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Revenue Today</h3>
                <div class="mt-2 flex items-baseline">
                    <div class="text-2xl font-semibold" id="todayRevenue">â‚º0</div>
                    <div class="ml-2 text-sm text-green-500" id="revenueTrend">+0%</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Active CarWashes</h3>
                <div class="mt-2 flex items-baseline">
                    <div class="text-2xl font-semibold" id="activeCarwashes">0</div>
                    <div class="ml-2 text-sm text-gray-500">of total</div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Revenue Trend</h3>
                <canvas id="revenueChart"></canvas>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Bookings by Hour</h3>
                <canvas id="bookingsChart"></canvas>
            </div>
        </div>

        <!-- Live Feed -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Live Activity</h3>
            <div id="activityFeed" class="space-y-4">
                <!-- Activity items will be added here -->
            </div>
        </div>
    </div>

    <script src="../../../frontend/js/admin/analytics-dashboard.js"></script>
</body>

</html>



