<?php
session_start();
require_once '../../backend/includes/db.php';
require_once '../../backend/includes/auth_check.php';
require_once '../../backend/includes/maps_config.php';
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Zone Mapper</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars(getenv('GOOGLE_MAPS_API_KEY')) ?>&libraries=drawing"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div id="map" class="w-full h-[600px] rounded-lg"></div>

            <div class="mt-4 space-y-4">
                <div class="flex items-center space-x-4">
                    <button id="drawZone" class="bg-blue-600 text-white px-4 py-2 rounded">
                        Draw Zone
                    </button>
                    <button id="saveZone" class="bg-green-600 text-white px-4 py-2 rounded">
                        Save Zone
                    </button>
                </div>

                <div id="zoneProperties" class="hidden">
                    <input type="text" id="zoneName" placeholder="Zone Name"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <input type="number" id="multiplier" placeholder="Price Multiplier"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>
        </div>
    </div>
</body>

</html>
