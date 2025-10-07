<?php
require 'vendor/autoload.php';
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

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
        $this->db = new mysqli(
            'localhost',
            'root',
            '',
            'carwash_db'
        );
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg);
        
        if ($data->type === 'subscribe_analytics') {
            $this->subscriptions[$from->resourceId] = [
                'role' => $data->role
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