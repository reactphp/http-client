<?php

namespace React\HttpClient;

use React\SocketClient\ConnectorInterface;

class ConnectorPair
{
    private $connector;
    private $secureConnector;

    public function __construct(ConnectorInterface $connector, ConnectorInterface $secureConnector)
    {
        $this->connector = $connector;
        $this->secureConnector = $secureConnector;
    }

    public function getConnectorForScheme($scheme)
    {
        return ('https' === $scheme) ? $this->secureConnector : $this->connector;
    }
}
