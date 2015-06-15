<?php

namespace React\Tests\HttpClient;

use React\HttpClient\RequestData;

class RequestDataTest extends TestCase
{
    /** @test */
    public function toStringReturnsHTTPRequestMessage()
    {
        $requestData = new RequestData('GET', 'http://www.example.com');

        $expected = "GET / HTTP/1.1\r\n" .
            "Host: www.example.com\r\n" .
            "User-Agent: React/alpha\r\n" .
            "Connection: close\r\n" .
            "\r\n";

        $this->assertSame($expected, $requestData->__toString());
    }

    /** @test */
    public function toStringUsesUserPassFromURL()
    {
        $requestData = new RequestData('GET', 'http://john:dummy@www.example.com');

        $expected = "GET / HTTP/1.1\r\n" .
            "Host: www.example.com\r\n" .
            "User-Agent: React/alpha\r\n" .
            "Connection: close\r\n" .
            "Authorization: Basic am9objpkdW1teQ==\r\n" .
            "\r\n";

        $this->assertSame($expected, $requestData->__toString());
    }
}
