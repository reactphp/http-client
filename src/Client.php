<?php

namespace React\HttpClient;

class Client
{
    private $connectorPair;

    public function __construct(ConnectorPair $connectorPair)
    {
        $this->connectorPair = $connectorPair;
    }

    public function request($method, $url, array $headers = [], array $options = null)
    {
        $requestData = new RequestData($method, $url, $headers);
        $requestOptions = new RequestOptions($options);

        return new Request($this->connectorPair, $requestData, $requestOptions);
    }
}
