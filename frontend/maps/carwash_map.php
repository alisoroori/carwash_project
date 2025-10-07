<?php
require_once '../../backend/includes/auth_check.php';
require_once '../../backend/includes/maps_config.php';

$mapsConfig = new MapsConfig();
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Find CarWash Services</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= $mapsConfig->getApiKey() ?>&libraries=places"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Search Box -->
        <div class="mb-6">
            <input type="text"
                id="searchLocation"
                class="w-full p-3 rounded-lg border border-gray-300"
                placeholder="Enter your location">
        </div>

        <!-- Map Container -->
        <div class="bg-white rounded-lg shadow-lg">
            <div id="map" class="w-full h-[600px] rounded-lg"></div>
        </div>

        <!-- CarWash List -->
        <div id="carwashList" class="mt-6 space-y-4">
            <!-- CarWash items will be loaded here -->
        </div>
    </div>

    <script src="/carwash_project/frontend/js/maps/carwash-map.js"></script>
</body>

</html>