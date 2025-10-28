<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';
require_once '../../includes/dispute_handler.php';

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /carwash_project/frontend/auth/login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Dispute Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Dispute Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Open Disputes</h3>
                <div class="mt-2 text-2xl font-semibold" id="openDisputes">0</div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Average Resolution Time</h3>
                <div class="mt-2 text-2xl font-semibold" id="avgResolutionTime">0 days</div>
            </div>
        </div>

        <!-- Dispute List -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Active Disputes</h2>
            <div id="disputesList" class="space-y-4">
                <!-- Disputes will be loaded here -->
            </div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/admin/dispute-management.js"></script>
</body>

</html>
