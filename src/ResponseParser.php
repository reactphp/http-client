<?php

namespace React\HttpClient;

class ResponseParser
{
    public function parseResponse($raw)
    {
        if (false === strpos($raw, "\r\n\r\n")) {
            throw new \InvalidArgumentException("Parameter is not a valid http response");
        }

        list($head, $body) = explode("\r\n\r\n", $raw, 2);

        $lines = explode("\r\n", $head);

        list($http, $code, $reason) = explode(' ', array_shift($lines), 3);
        list($protocol, $version) = explode('/', $http, 2);

        $headers = [];
        foreach ($lines as $line) {
            list($header, $value) = array_map('trim', explode(':', $line, 2));

            if (!isset($headers[$header])) {
                $headers[$header] = $value;
            } elseif (!is_array($headers[$header])) {
                $headers[$header] = [ $headers[$header], $value ];
            } else {
                $headers[$header][] = $value;
            }
        }

        return [
            'protocol' => $protocol,
            'version' => $version,
            'code' => $code,
            'reason_phrase' => $reason,
            'headers' => $headers,
            'body' => $body
        ];
    }
}
