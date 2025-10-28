<?php
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
    <title>Zone Pricing Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Zone Management Form -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Add/Edit Zone</h2>
            <form id="zoneForm" class="space-y-4">
                <input type="hidden" id="zoneId" name="zone_id">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Zone Name</label>
                        <input type="text" id="zoneName" name="zone_name" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Price Multiplier</label>
                        <input type="number" id="multiplier" name="multiplier" step="0.1" min="1"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">
                    Save Zone
                </button>
            </form>
        </div>

        <!-- Zones List -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Current Zones</h2>
            <div id="zonesList" class="divide-y divide-gray-200">
                <!-- Zones will be loaded here -->
            </div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/admin/zone-pricing.js"></script>
</body>

</html>
