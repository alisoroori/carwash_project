<?php
declare(strict_types=1);

namespace App\Classes\Websocket;

class VisualizationServer implements MessageComponentInterface
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

        if (!is_object($data)) {
            // ignore invalid messages
            return;
        }

        if (isset($data->type) && $data->type === 'subscribe' && isset($data->charts)) {
            $this->subscriptions[$from->resourceId] = $data->charts;
            $this->sendInitialData($from, $data->charts);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        unset($this->subscriptions[$conn->resourceId]);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
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

    private function fetchChartData($chart)
    {
        // TODO: Replace this stub with real data retrieval (use App\Classes\Database)
        return ['labels' => [], 'values' => []];
    }
}

