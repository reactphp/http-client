<?php

namespace React\HttpClient;

use InvalidArgumentException;

class RequestData
{
    private $method;
    private $url;
    private $headers;

    private $protocolVersion = '1.1';

    public function __construct($method, $url, array $headers = [])
    {
        $this->method = $method;
        $this->url = $url;
        $this->headers = $headers;
    }

    private function mergeDefaultheaders(array $headers)
    {
        $port = ($this->getDefaultPort() === $this->getPort()) ? '' : ":{$this->getPort()}";
        $connectionHeaders = ('1.1' === $this->protocolVersion) ? array('Connection' => 'close') : array();

        return array_merge(
            array(
                'Host'          => $this->getHost().$port,
                'User-Agent'    => 'React/alpha',
            ),
            $connectionHeaders,
            $headers
        );
    }

    public function getMethod()
    {
        return strtoupper($this->method);
    }

    public function getUrl()
    {
        return $this->url;
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

    public function __toString()
    {
        $headers = $this->mergeDefaultheaders($this->headers);

        $data = '';
        $data .= "{$this->method} {$this->getPath()} HTTP/{$this->protocolVersion}\r\n";
        foreach ($headers as $name => $value) {
            $data .= "$name: $value\r\n";
        }
        $data .= "\r\n";

        return $data;
    }

    /**
     * Processes a redirect request, updating internal values to represent the new request data.
     *
     * @param integer $code     HTTP status code for the redirect.
     * @param string  $location The Location header received.
     */
    public function redirect($code, $location)
    {
        switch ($code) {

            //These cases require that we switch to the GET method.
            //@see https://github.com/bagder/curl/blob/cc28bc472ec421cec2ba26d653e53892998a248d/lib/transfer.c#L1736
            case 301:
            case 302:
                $this->method = 'GET';

            //Note: 303, 307, 308 status is not supported in HTTP/1.0.
            // case 303:
            // case 307:
            // case 308:

                //Of course switch the location.
                $this->url = $location;
                break;

            default:
                throw new InvalidArgumentException(sprintf("Redirect code %u is not supported", $code));
        }
    }
}
