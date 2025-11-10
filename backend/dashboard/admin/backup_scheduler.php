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
    <title>Backup Scheduler</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
</head>

<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">Backup Management</h1>

            <!-- Schedule Configuration -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold mb-4">Backup Schedule</h2>
                <form id="scheduleForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Frequency</label>
                            <select id="frequency" name="frequency" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Time</label>
                            <input type="time" id="backupTime" name="time" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Retention (days)</label>
                            <input type="number" id="retention" name="retention" min="1" max="365" value="7"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Save Schedule
                        </button>
                    </div>
                </form>
            </div>

            <!-- Backup History -->
            <div>
                <h2 class="text-lg font-semibold mb-4">Backup History</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="backupHistory" class="bg-white divide-y divide-gray-200">
                            <!-- Backup history will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="../../../frontend/js/admin/backup-scheduler.js"></script>
</body>

</html>



