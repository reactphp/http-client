<?php

namespace React\HttpClient;

use React\SocketClient\ConnectorInterface;

class Client
{
    private $connector;
    private $secureConnector;

    public function __construct(ConnectorInterface $connector, ConnectorInterface $secureConnector)
    {
        $this->connector = $connector;
        $this->secureConnector = $secureConnector;
    }

    public function request($method, $url, array $headers = [])
    {
        $requestData = new RequestData($method, $url, $headers);
        $connector = $this->getConnectorForScheme($requestData->getScheme());

        return new Request($connector, $requestData);
    }

    private function getConnectorForScheme($scheme)
    {
        return ('https' === $scheme) ? $this->secureConnector : $this->connector;
    }
}
