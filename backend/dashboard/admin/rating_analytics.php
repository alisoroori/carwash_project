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
    <title>Rating Analytics - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Rating Distribution -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Rating Distribution</h2>
                <canvas id="ratingDistribution"></canvas>
            </div>

            <!-- Trend Analysis -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Rating Trends</h2>
                <canvas id="ratingTrends"></canvas>
            </div>

            <!-- Top Rated CarWashes -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Top Rated CarWashes</h2>
                <div id="topRated" class="space-y-4">
                    <!-- Top rated list will be loaded here -->
                </div>
            </div>

            <!-- Recent Reviews -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4">Recent Reviews</h2>
                <div id="recentReviews" class="space-y-4">
                    <!-- Recent reviews will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/admin/rating-analytics.js"></script>
</body>

</html>