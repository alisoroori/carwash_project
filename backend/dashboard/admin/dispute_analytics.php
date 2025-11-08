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
    <title>Dispute Analytics Dashboard</title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/tailwind.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Analytics Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Total Disputes</h3>
                <div class="mt-2 text-2xl font-semibold" id="totalDisputes">0</div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Resolution Rate</h3>
                <div class="mt-2 text-2xl font-semibold" id="resolutionRate">0%</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Dispute Reasons</h2>
                <canvas id="disputeReasonsChart"></canvas>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Resolution Timeline</h2>
                <canvas id="resolutionTimelineChart"></canvas>
            </div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/admin/dispute-analytics.js"></script>
</body>

</html>

