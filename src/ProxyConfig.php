<?php

namespace React\HttpClient;

class ProxyConfig
{
    public $host;
    public $port;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
    }
}
