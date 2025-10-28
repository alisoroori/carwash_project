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
    <title>Review Moderation - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-6">Review Moderation</h1>

            <!-- Filters -->
            <div class="mb-6 flex space-x-4">
                <select id="statusFilter" class="rounded-md border-gray-300">
                    <option value="pending">Pending Reviews</option>
                    <option value="approved">Approved Reviews</option>
                    <option value="rejected">Rejected Reviews</option>
                </select>
            </div>

            <!-- Reviews List -->
            <div id="reviewsList" class="space-y-4">
                <!-- Reviews will be loaded here -->
            </div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/admin/review-moderation.js"></script>
</body>

</html>
