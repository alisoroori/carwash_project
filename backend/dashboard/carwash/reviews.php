<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

// Check if user is a car wash owner
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'carwash') {
    header('Location: ../login.php');
    exit();
}

// Get car wash ID
$carwashId = $_SESSION['carwash_id'];
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeÄŸerlendirmeler - CarWash Panel</title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-gray-500 text-sm mb-1">Ortalama Puan</div>
                <div class="text-2xl font-bold text-yellow-500" id="avgRating">-</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-gray-500 text-sm mb-1">Toplam DeÄŸerlendirme</div>
                <div class="text-2xl font-bold" id="totalReviews">-</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-gray-500 text-sm mb-1">Son 30 GÃ¼n</div>
                <div class="text-2xl font-bold" id="recentReviews">-</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-gray-500 text-sm mb-1">YanÄ±t OranÄ±</div>
                <div class="text-2xl font-bold text-green-500" id="responseRate">-</div>
            </div>
        </div>

        <!-- Reviews List -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-lg font-bold">DeÄŸerlendirmeler</h2>
            </div>
            <div id="reviewsList" class="divide-y">
                <!-- Reviews will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium">DeÄŸerlendirmeye YanÄ±t Ver</h3>
                <form id="replyForm" class="mt-4">
                    <textarea name="reply" rows="4"
                        class="w-full rounded-md border-gray-300 shadow-sm"
                        placeholder="YanÄ±tÄ±nÄ±zÄ± yazÄ±n..."></textarea>
                    <div class="mt-4 flex justify-end space-x-2">
                        <button type="button" onclick="closeReplyModal()"
                            class="px-4 py-2 text-gray-500 hover:text-gray-700">
                            Ä°ptal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            YanÄ±tla
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../frontend/js/carwash/reviews.js"></script>
</body>

</html>

