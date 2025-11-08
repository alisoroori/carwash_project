<?php
session_start();
require_once '../../backend/includes/db.php';
require_once '../../backend/includes/auth_check.php';
require_once '../../backend/includes/maps_config.php';

$mapsConfig = new MapsConfig();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Track Mobile Service</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/carwash_project/frontend/css/tailwind.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= $mapsConfig->getApiKey() ?>"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Service Info -->
        <div class="bg-white rounded-lg shadow-lg p-4 mb-4">
            <h2 class="text-xl font-semibold mb-2" id="serviceStatus">Loading...</h2>
            <p class="text-gray-600" id="estimatedTime"></p>
        </div>

        <!-- Map -->
        <div class="bg-white rounded-lg shadow-lg">
            <div id="trackingMap" class="w-full h-[400px] rounded-lg"></div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/carwash/tracking/service-progress.js"></script>
</body>

</html>

