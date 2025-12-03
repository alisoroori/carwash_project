<?php
namespace App\Classes\Websocket;

class AnalyticsServer implements MessageComponentInterface {
    protected $clients;
    protected $subscriptions;
    protected $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        $this->connectDatabase();
    }

    protected function connectDatabase() {
        $this->db = new \mysqli(
            'localhost',
            'root',
            '',
            'carwash_db'
        );

        if ($this->db->connect_error) {
            throw new \RuntimeException('Database connection failed: ' . $this->db->connect_error);
        }

        // Ensure UTF-8
        $this->db->set_charset('utf8mb4');
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg);
        if (!is_object($data)) {
            return;
        }
        
        if (isset($data->type) && $data->type === 'subscribe_analytics') {
            $this->subscriptions[$from->resourceId] = [
                'role' => $data->role ?? null
            ];
            $this->sendAnalyticsUpdate($from);
        }
    }

    protected function sendAnalyticsUpdate($client) {
        $analytics = $this->collectAnalyticsData();
        $client->send(json_encode($analytics));
    }

    protected function collectAnalyticsData() {
        // Get real-time stats from database
        $today = date('Y-m-d');
        
        $stats = [
            'activeUsers' => $this->getActiveUsers(),
            'todayBookings' => $this->getTodayBookings($today),
            'todayRevenue' => $this->getTodayRevenue($today),
            'activeCarwashes' => $this->getActiveCarwashes(),
            'latestActivity' => $this->getLatestActivity()
        ];

        return $stats;
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        unset($this->subscriptions[$conn->resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function broadcastAnalytics() {
        $analytics = $this->collectAnalyticsData();
        foreach ($this->clients as $client) {
            if (isset($this->subscriptions[$client->resourceId])) {
                $client->send(json_encode($analytics));
            }
        }
    }

    // Minimal safe implementations to avoid runtime errors
    protected function getActiveUsers(): int {
        $sql = "SELECT COUNT(*) AS cnt FROM users WHERE status = 'active'";
        $res = $this->db->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            return (int)$row['cnt'];
        }
        return 0;
    }

    protected function getTodayBookings(string $today): int {
        $date = $this->db->real_escape_string($today);
        $sql = "SELECT COUNT(*) AS cnt FROM bookings WHERE DATE(created_at) = '{$date}'";
        $res = $this->db->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            return (int)$row['cnt'];
        }
        return 0;
    }

    protected function getTodayRevenue(string $today): float {
        $date = $this->db->real_escape_string($today);
        $sql = "SELECT IFNULL(SUM(amount),0) AS total FROM payments WHERE DATE(created_at) = '{$date}'";
        $res = $this->db->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            return (float)$row['total'];
        }
        return 0.0;
    }

    protected function getActiveCarwashes(): int {
    $sql = "SELECT COUNT(*) AS cnt FROM carwashes WHERE LOWER(COALESCE(status,'')) IN ('açık','acik','open','active','1') AND COALESCE(is_active,0) = 1";
        $res = $this->db->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            return (int)$row['cnt'];
        }
        return 0;
    }

    protected function getLatestActivity(int $limit = 5): array {
        $limit = (int)$limit;
        $sql = "SELECT id, type, message, created_at FROM activity_log ORDER BY created_at DESC LIMIT {$limit}";
        $res = $this->db->query($sql);
        $items = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $items[] = $row;
            }
        }
        return $items;
    }
}

// Create and run the server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new AnalyticsServer()
        )
    ),
    8081
);

echo "Analytics WebSocket Server running on port 8081\n";
$server->run();
