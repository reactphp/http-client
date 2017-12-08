<?php

namespace React\HttpClient;

interface ClientInterface
{
    /**
     * @param $method
     * @param $url
     * @param array $headers
     * @param string $protocolVersion
     *
     * @return mixed
     */
    public function request($method, $url, array $headers, $protocolVersion);
}
