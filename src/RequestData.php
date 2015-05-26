<?php

namespace React\HttpClient;

class RequestData
{
    private $method;
    private $url;
    private $headers;
    private $protocolVersion;

    public function __construct($method, $url, array $headers = [], $protocolVersion = '1.0')
    {
        $this->method = $method;
        $this->url = $url;
        $this->headers = $headers;
        $this->protocolVersion = $protocolVersion;
    }

    private function mergeDefaultheaders(array $headers)
    {
        $port = ($this->getDefaultPort() === $this->getPort()) ? '' : ":{$this->getPort()}";
        $connectionHeaders = ('1.1' === $this->protocolVersion) ? array('Connection' => 'close') : array();
        $authHeaders = $this->getAuthHeaders();

        return array_merge(
            array(
                'Host'          => $this->getHost().$port,
                'User-Agent'    => 'React/alpha',
            ),
            $connectionHeaders,
            $authHeaders,
            $headers
        );
    }

    public function getScheme()
    {
        return parse_url($this->url, PHP_URL_SCHEME);
    }

    public function getHost()
    {
        return parse_url($this->url, PHP_URL_HOST);
    }

    public function getPort()
    {
        return (int) parse_url($this->url, PHP_URL_PORT) ?: $this->getDefaultPort();
    }

    public function getDefaultPort()
    {
        return ('https' === $this->getScheme()) ? 443 : 80;
    }

    public function getPath()
    {
        $path = parse_url($this->url, PHP_URL_PATH) ?: '/';
        $queryString = parse_url($this->url, PHP_URL_QUERY);

        return $path.($queryString ? "?$queryString" : '');
    }

    public function setProtocolVersion($version)
    {
        $this->protocolVersion = $version;
    }

    private function getUrlUserPass()
    {
        $components = parse_url($this->url);

        if (isset($components['user'])) {
            return array(
                'user' => $components['user'],
                'pass' => isset($components['pass']) ? $components['pass'] : null,
            );
        }
    }

    private function getAuthHeaders()
    {
        if (null !== $auth = $this->getUrlUserPass()) {
            return array(
                'Authorization' => 'Basic ' . base64_encode($auth['user'].':'.$auth['pass']),
            );
        }

        return array();
    }

    public function toStringURI($uri)
    {
        $headers = $this->mergeDefaultheaders($this->headers);

        $data = '';
        $data .= "{$this->method} {$uri} HTTP/{$this->protocolVersion}\r\n";
        foreach ($headers as $name => $value) {
            $data .= "$name: $value\r\n";
        }
        $data .= "\r\n";

        return $data;
    }

    public function toString()
    {
        return $this->toStringURI($this->getPath());
    }

    public function toStringAbsolute()
    {
        return $this->toStringURI($this->url);
    }

    public function __toString()
    {
        return $this->toString();
    }
}
