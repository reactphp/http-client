<?php

namespace React\HttpClient;

use React\EventLoop\LoopInterface;
use React\Socket\ConnectorInterface;
use React\Socket\Connector;

class Client
{
    private $connector;

    public function __construct(LoopInterface $loop, ConnectorInterface $connector = null)
    {
        if ($connector === null) {
            $connector = new KeepaliveConnector(new Connector($loop));
        }

        $this->connector = new KeepaliveConnector($connector);
    }

    public function request($method, $url, array $headers = array(), $protocolVersion = '1.0')
    {
        $requestData = new RequestData($method, $url, $headers, $protocolVersion);

        $that = $this;
        $request = new Request($this->connector, $requestData);
        $request->on('close', function() use ($that, $url) {
            $that->connector->handleConnectionClose($url);
        });
        $request->on('response',
            function(Response $response) use ($url, $headers, $that) {
                if ($that->isConnectionClose($headers)
                    || $that->isConnectionClose($response->getHeaders())) {
                    $that->connector->handleConnectionClose($url);
                }
            }
        );

        return $request;
    }

    /** @internal */
    public function isConnectionClose($headers)
    {
        $normalizedHeaders = array_change_key_case($headers, CASE_LOWER);

        if (!isset($normalizedHeaders['connection'])) {
            return false;
        }

        return strtolower($normalizedHeaders['connection']) === 'close';
    }
}
