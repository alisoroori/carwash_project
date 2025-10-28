<?php
declare(strict_types=1);

namespace App\Classes\Websocket;

class DashboardWebSocket implements MessageComponentInterface
{
    protected $clients;
    protected $subscriptions;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg);

        if ($data->type === 'subscribe') {
            $this->subscriptions[$from->resourceId] = [
                'carwashId' => $data->carwashId,
                'role' => $data->role
            ];
        }

        // Broadcast updates to relevant clients
        foreach ($this->clients as $client) {
            if (
                isset($this->subscriptions[$client->resourceId]) &&
                $this->subscriptions[$client->resourceId]['carwashId'] === $data->carwashId
            ) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        unset($this->subscriptions[$conn->resourceId]);
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
    }

    private function broadcastLocationUpdate($data) {
        foreach ($this->subscriptions[$data->booking_id] ?? [] as $client) {
            $client->send(json_encode([
                'type' => 'location_update',
                'latitude' => $data->latitude,
                'longitude' => $data->longitude,
                'booking_id' => $data->booking_id
            ]));
        }
    }
}

// Run the server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new DashboardWebSocket()
        )
    ),
    8080
);

$server->run();

