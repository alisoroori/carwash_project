<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Advanced Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/d3@7"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Interactive Chart Controls -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-4 gap-4">
                <select id="chartType" class="rounded-md border-gray-300">
                    <option value="line">Line Chart</option>
                    <option value="bar">Bar Chart</option>
                    <option value="heatmap">Heatmap</option>
                </select>
                <select id="timeRange" class="rounded-md border-gray-300">
                    <option value="7">Last 7 Days</option>
                    <option value="30">Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                </select>
            </div>
        </div>

        <!-- Visualization Area -->
        <div class="grid grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <canvas id="mainChart"></canvas>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div id="heatmapViz"></div>
            </div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/admin/report-visualizer.js"></script>
</body>

</html>
