<?php

namespace React\HttpClient;

use React\Socket\ConnectorInterface;

class Client implements ClientInterface
{
    private $connector;

    public function __construct(ConnectorInterface $connector)
    {
        $this->connector = $connector;
    }

    public function request($method, $url, array $headers = [], $protocolVersion = '1.0')
    {
        $requestData = new RequestData($method, $url, $headers, $protocolVersion);

        return new Request($this->connector, $requestData);
    }
}
