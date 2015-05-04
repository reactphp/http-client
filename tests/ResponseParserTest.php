<?php

namespace React\Tests\HttpClient;

use React\HttpClient\ResponseParser;

class ResponseParserTest extends TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new ResponseParser;
    }

    /**
     * @dataProvider dataProvider
     */
    public function testParse($raw, $expected, $msg = '')
    {
        $this->assertEquals($expected, $this->parser->parse($raw), $msg);
    }

    public function dataProvider()
    {
        return [
            [ "", false, 'empty' ],
            [ "MWA-HA-HA\r\n\r\nNOT A VALID\r\nRESPONSE", false, 'invalid' ],
            [ "HTTP/1.0 200 OK\r\nContent-Length: 4\r\nContent-Type: text/plain;charset=utf-8\r\n\r\nTest", [
                'protocol' => 'HTTP',
                'version' => '1.0',
                'code' => 200,
                'reason' => 'OK',
                'headers' => [
                    'content-length' => [
                        [
                            4
                        ]
                    ],
                    'content-type' => [
                        [
                            'text/plain',
                            'charset=utf-8'
                        ]
                    ]
                ],
                'body' => 'Test'
            ], 'valid' ],
            [ "HTTP/1.0 404\r\n\r\n", [
                'protocol' => 'HTTP',
                'version' => '1.0',
                'code' => 404,
                'reason' => '',
                'headers' => [],
                'body' => ''
            ], 'missing reason' ],
            [ "/1.0 404 Not Found\r\n\r\n", false, 'missing protocol 1' ],
            [ "1.0 404 Not Found\r\n\r\n", false, 'missing protocol 2' ],
            [ "HTTP/ 404 Not Found\r\n\r\n", false, 'missing version 1' ],
            [ "HTTP 404 Not Found\r\n\r\n", false, 'missing version 2' ],
            [ "HTTP/1.0 Not Found\r\n\r\n", false, 'missing code' ],
            [ "FTP/1.0 404 Not Found\r\n\r\n", false, 'invalid protocol' ],
            [ "HTTP/1.1 404 Not Found\r\n\r\n", false, 'invalid version' ],
            [ "HTTP/1.0 42 Answer to the Ultimate Question\r\n\r\n", false, 'invalid code' ],
            [ "HTTP/1.0 200 OK\r\nContent-Length 4\r\n\r\nTest", [
                'protocol' => 'HTTP',
                'version' => '1.0',
                'code' => 200,
                'reason' => 'OK',
                'headers' => [],
                'body' => 'Test'
            ], 'missing colon' ],
            [ "HTTP/1.0 200 OK\r\n: 42\r\n\r\nTest", [
                'protocol' => 'HTTP',
                'version' => '1.0',
                'code' => 200,
                'reason' => 'OK',
                'headers' => [],
                'body' => 'Test'
            ], 'missing header name' ],
            [ "HTTP/1.0 200 OK\r\nX-Header-Name: foo\r\nX-Header-Name: bar;baz=1\r\n\r\nTest", [
                'protocol' => 'HTTP',
                'version' => '1.0',
                'code' => 200,
                'reason' => 'OK',
                'headers' => [
                    'x-header-name' => [
                        [
                            'foo'
                        ],
                        [
                            'bar',
                            'baz=1'
                        ]
                    ]
                ],
                'body' => 'Test'
            ], 'multiple headers with the same name' ],
        ];
    }
}
