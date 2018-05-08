<?php

namespace React\HttpClient;

use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\ConnectorInterface;

/**
 * @internal
 */
class KeepaliveConnector implements ConnectorInterface
{
    private $directConnector;

    /** @var PromiseInterface[] */
    private $aliveConnections = array();

    public function __construct(ConnectorInterface $directConnector)
    {
        $this->directConnector = $directConnector;
    }

    public function connect($uri)
    {
        return isset($this->aliveConnections[$uri])
            ? $this->tryReuseAliveConnection($uri)
            : $this->createNewConnection($uri);
    }

    public function handleConnectionClose($uri)
    {
        unset($this->aliveConnections[$uri]);
    }

    private function tryReuseAliveConnection($uri)
    {
        return $this->aliveConnections[$uri];
    }

    private function createNewConnection($uri)
    {
        $connection = $this->directConnector->connect($uri);
        $this->aliveConnections[$uri] = $connection;
        return $connection;
    }
}
