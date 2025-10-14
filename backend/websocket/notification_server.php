<?php
require 'vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class NotificationServer implements MessageComponentInterface
{
    protected $clients;
    protected $userConnections;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        // Parse query string for user ID
        $query = parse_url($conn->httpRequest->getUri(), PHP_URL_QUERY);
        parse_str($query ?? '', $params);

        if (isset($params['userId'])) {
            $this->userConnections[$params['userId']][] = $conn;
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg);

        // Handle different message types
        switch ($data->type) {
            case 'review_notification':
                $this->broadcastToUser($data->userId, [
                    'type' => 'review_update',
                    'data' => $data->review
                ]);
                break;
        }
    }

    protected function broadcastToUser($userId, $message)
    {
        if (isset($this->userConnections[$userId])) {
            foreach ($this->userConnections[$userId] as $conn) {
                $conn->send(json_encode($message));
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
    }
}

// Create and run the server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new NotificationServer()
        )
    ),
    8083
);

$server->run();
