<?php
declare(strict_types=1);

namespace App\Classes\Websocket;

require_once __DIR__ . '/../../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class NotificationServer implements MessageComponentInterface
{
    protected \SplObjectStorage $clients;
    protected array $userConnections;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        // Parse query string for user ID (safely)
        $request = $conn->httpRequest ?? null;
        $query = null;
        if ($request !== null) {
            $uri = $request->getUri();
            $query = parse_url((string)$uri, PHP_URL_QUERY);
        }
        parse_str($query ?? '', $params);

        if (!empty($params['userId'])) {
            $this->userConnections[$params['userId']][] = $conn;
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg);
        if (!is_object($data) || !isset($data->type)) {
            return;
        }

        // Handle different message types
        switch ($data->type) {
            case 'review_notification':
                if (isset($data->userId) && isset($data->review)) {
                    $this->broadcastToUser($data->userId, [
                        'type' => 'review_update',
                        'data' => $data->review
                    ]);
                }
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

        // Remove connection from any user lists
        foreach ($this->userConnections as $userId => $conns) {
            $index = array_search($conn, $conns, true);
            if ($index !== false) {
                unset($this->userConnections[$userId][$index]);
                $this->userConnections[$userId] = array_values($this->userConnections[$userId]);
                if (empty($this->userConnections[$userId])) {
                    unset($this->userConnections[$userId]);
                }
                break;
            }
        }
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
