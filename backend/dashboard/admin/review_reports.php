<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/report_manager.php';

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$reportManager = new ReportManager($conn);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeÄŸerlendirme RaporlarÄ± - Admin Panel</title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">DeÄŸerlendirme RaporlarÄ±</h1>

        <!-- Filters -->
        <div class="mb-6 flex gap-4">
            <select id="statusFilter" class="rounded-md border-gray-300 shadow-sm">
                <option value="">TÃ¼m Durumlar</option>
                <option value="pending">Beklemede</option>
                <option value="resolved">Ã‡Ã¶zÃ¼ldÃ¼</option>
                <option value="dismissed">Reddedildi</option>
            </select>
            <select id="reasonFilter" class="rounded-md border-gray-300 shadow-sm">
                <option value="">TÃ¼m Nedenler</option>
                <option value="spam">Spam</option>
                <option value="offensive">RahatsÄ±z Edici</option>
                <option value="inappropriate">Uygunsuz</option>
                <option value="other">DiÄŸer</option>
            </select>
        </div>

        <!-- Reports Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Tarih
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            DeÄŸerlendirme
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Bildirim Nedeni
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Durum
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            Ä°ÅŸlemler
                        </th>
                    </tr>
                </thead>
                <tbody id="reportsTableBody" class="bg-white divide-y divide-gray-200">
                    <!-- Reports will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Report Detail Modal -->
    <div id="reportModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div id="reportDetail">
                <!-- Report details will be loaded here -->
            </div>
        </div>
    </div>

    <script src="../../frontend/js/admin/review_reports.js"></script>
</body>
</html>

