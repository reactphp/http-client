<?php

namespace React\Tests\HttpClient;

use React\HttpClient\DecodeChunkedStream;
use React\Stream\ThroughStream;

class DecodeChunkedStreamTest extends TestCase
{
    public function provideChunkedEncoding()
    {
        return [
            [["4\r\nWiki\r\n5\r\npedia\r\ne\r\n in\r\n\r\nchunks.\r\n0\r\n\r\n"]],
            [["4\r\nWiki\r\n", "5\r\npedia\r\ne\r\n in\r\n\r\nchunks.\r\n0\r\n\r\n"]],
            [["4\r\nWiki\r\n", "5\r\n", "pedia\r\ne\r\n in\r\n\r\nchunks.\r\n0\r\n\r\n"]],
            [["4\r\nWiki\r\n", "5\r\n", "pedia\r\ne\r\n in\r\n", "\r\nchunks.\r\n0\r\n\r\n"]],
            [["4\r\n", "Wiki\r\n", "5\r\n", "pedia\r\ne\r\n in\r\n", "\r\nchunks.\r\n0\r\n\r\n"]],
            [["4\r\n", "Wiki\r\n", "5\r\n", "pedia\r\ne\r\n", " in\r\n", "\r\nchunks.\r\n0\r\n\r\n"]],
            [["4\r\n", "Wiki\r\n", "5\r\n", "pedia\r\ne\r\n", " in\r\n", "\r\nchunks.\r\n", "0\r\n\r\n"]],
        ];
    }

    /**
     * @test
     * @dataProvider provideChunkedEncoding
     */
    public function testChunkedEncoding(array $strings)
    {
        $stream = new ThroughStream();
        $response = new DecodeChunkedStream($stream);
        $buffer = '';
        $response->on('data', function ($data) use (&$buffer) {

            echo PHP_EOL, '------------------' , PHP_EOL;
            echo 'CHUNK: ', $data;
            echo PHP_EOL, '------------------' , PHP_EOL;

            $buffer .= $data;
            $this->assertTrue(true);
        });
        foreach ($strings as $string) {
            $stream->write($string);
        }
        $this->assertSame("Wikipedia in\r\n\r\nchunks.", $buffer);
    }
}
