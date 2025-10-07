<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';

class NotificationDashboard
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getRealtimeStats()
    {
        $query = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                AVG(CASE WHEN status = 'sent' 
                    THEN TIMESTAMPDIFF(SECOND, created_at, updated_at) 
                    ELSE NULL END) as avg_delivery_time
            FROM notifications
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ";

        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <title>Notification Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Stats Overview -->
        <div class="grid grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Delivery Rate</h3>
                <div class="mt-2 text-2xl font-semibold" id="deliveryRate">0%</div>
            </div>
        </div>

        <!-- Real-time Feed -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Live Notifications</h2>
            <div id="notificationFeed" class="h-96 overflow-y-auto"></div>
        </div>
    </div>

    <script src="/carwash_project/frontend/js/admin/notification-monitor.js"></script>
</body>

</html>