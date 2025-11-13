<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';
require_once '../../includes/error_analytics.php';

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /carwash_project/frontend/auth/login.php');
    exit();
}

$errorAnalytics = new ErrorAnalytics($conn);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Error Monitoring Dashboard</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Live Error Feed -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Live Error Feed</h2>
            <div id="errorFeed" class="h-64 overflow-y-auto">
                <!-- Real-time errors will appear here -->
            </div>
        </div>

        <!-- Error Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Critical Errors</h3>
                <div class="mt-2 text-2xl font-semibold text-red-600" id="criticalCount">0</div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Resolution Time</h3>
                <div class="mt-2 text-2xl font-semibold" id="avgResolutionTime">0min</div>
            </div>
        </div>

        <!-- Error Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <canvas id="errorTrendsChart"></canvas>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <canvas id="errorTypesChart"></canvas>
            </div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/admin/error-monitor.js"></script>
</body>

</html>



