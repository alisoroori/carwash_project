<?php
session_start();
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';
require_once '../../includes/transaction_analytics.php';

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /carwash_project/frontend/auth/login.php');
    exit();
}

$analytics = new TransactionAnalytics($conn);
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Transaction Dashboard</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Total Revenue</h3>
                <div class="mt-2 flex items-baseline">
                    <div class="text-2xl font-semibold" id="totalRevenue">â‚º0</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Success Rate</h3>
                <div class="mt-2 flex items-baseline">
                    <div class="text-2xl font-semibold" id="successRate">0%</div>
                </div>
            </div>
        </div>

        <!-- Transaction List -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-lg font-semibold mb-4">Recent Transactions</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="transactionsList" class="divide-y divide-gray-200">
                        <!-- Transactions will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/admin/transaction-dashboard.js"></script>
</body>

</html>



