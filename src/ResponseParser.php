<?php

namespace React\HttpClient;

class ResponseParser
{
    public function parse($raw)
    {
        if (empty($raw) or false === strpos($raw, "\r\n\r\n")) {
            return false;
        }

        list($head, $body) = explode("\r\n\r\n", $raw, 2);

        $lines = explode("\r\n", $head);
        $first_line = array_shift($lines);

        if (!strpos($first_line, ' ')) {
            return false;
        }

        list($http, $code, $reason) = explode(' ', $first_line.' ', 3);

        if (!strpos($http, '/') or (int)$code < 100 or (int)$code >= 1000) {
            return false;
        }

        list($protocol, $version) = explode('/', $http, 2);

        if ($protocol !== 'HTTP') {
            return false;
        }

        $headers = [];
        foreach ($lines as $line) {
            if (!strpos($line, ':')) {
                continue;
            }

            list($name, $value) = array_map('trim', explode(':', $line, 2));

            $name = strtolower($name);

            if (empty($name)) {
                continue;
            }

            if (strpos($value, ';')) {
                $value = array_map('trim', explode(';', $value));
            } else {
                $value = [ $value ];
            }

            if (!isset($headers[$name])) {
                $headers[$name] = [];
            }

            $headers[$name] = array_merge($headers[$name], [ $value ]);
        }

        return [
            'protocol' => $protocol,
            'version' => $version,
            'code' => (int)$code,
            'reason' => trim($reason),
            'headers' => $headers,
            'body' => $body
        ];
    }
}
