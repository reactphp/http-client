<?php

namespace React\HttpClient;

use React\EventLoop\LoopInterface;
use React\Socket\ConnectorInterface;
use React\Socket\Connector;

class Client
{
    private $loop;
    private $connector;

    public function __construct(LoopInterface $loop, ConnectorInterface $connector = null)
    {
        if ($connector === null) {
            $connector = new Connector($loop);
        }

        $this->loop = $loop;
        $this->connector = $connector;
    }

    public function request($method, $url, array $headers = array(), $protocolVersion = '1.0')
    {
        $requestData = new RequestData($method, $url, $headers, $protocolVersion);

        $request = new Request($this->connector, $requestData);
        $request->setLoop($this->loop);

        return $request;
    }
}
