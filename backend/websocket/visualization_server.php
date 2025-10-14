<?php

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require 'vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class VisualizationServer implements MessageComponentInterface
{
    protected $clients;
    protected $subscriptions;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg);

        if ($data->type === 'subscribe') {
            $this->subscriptions[$from->resourceId] = $data->charts;
            $this->sendInitialData($from, $data->charts);
        }
    }

    private function sendInitialData($client, $charts)
    {
        foreach ($charts as $chart) {
            $data = $this->fetchChartData($chart);
            $client->send(json_encode([
                'type' => 'chart_update',
                'chartId' => $chart,
                'data' => $data
            ]));
        }
    }
}
