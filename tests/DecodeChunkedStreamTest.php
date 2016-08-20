<?php

namespace React\Tests\HttpClient;

use Exception;
use React\HttpClient\ChunkedStreamDecoder;
use React\Stream\ThroughStream;

class DecodeChunkedStreamTest extends TestCase
{
    public function provideChunkedEncoding()
    {
        return [
            [
                ["4\r\nWiki\r\n5\r\npedia\r\ne\r\n in\r\n\r\nchunks.\r\n0\r\n\r\n"],
            ],
            [
                ["4\r\nWiki\r\n", "5\r\npedia\r\ne\r\n in\r\n\r\nchunks.\r\n0\r\n\r\n"],
            ],
            [
                ["4\r\nWiki\r\n", "5\r\n", "pedia\r\ne\r\n in\r\n\r\nchunks.\r\n0\r\n\r\n"],
            ],
            [
                ["4\r\nWiki\r\n", "5\r\n", "pedia\r\ne\r\n in\r\n", "\r\nchunks.\r\n0\r\n\r\n"],
            ],
            [
                ["4\r\n", "Wiki\r\n", "5\r\n", "pedia\r\ne\r\n in\r\n", "\r\nchunks.\r\n0\r\n\r\n"],
            ],
            [
                ["4\r\n", "Wiki\r\n", "5\r\n", "pedia\r\ne; foo=[bar,beer,pool,cue,win,won]\r\n", " in\r\n", "\r\nchunks.\r\n0\r\n\r\n"],
            ],
            [
                ["4; foo=bar\r\n", "Wiki\r\n", "5\r\n", "pedia\r\ne\r\n", " in\r\n", "\r\nchunks.\r\n", "0\r\n\r\n"],
            ],
            [
                str_split("4\r\nWiki\r\n5\r\npedia\r\ne\r\n in\r\n\r\nchunks.\r\n0\r\n\r\n"),
            ],
            [
                str_split("6\r\nWi\r\nki\r\n5\r\npedia\r\ne\r\n in\r\n\r\nchunks.\r\n0\r\n\r\n"),
                "Wi\r\nkipedia in\r\n\r\nchunks."
            ],
            [
                ["6\r\nWi\r\n", "ki\r\n5\r\npedia\r\ne\r\n in\r\n\r\nchunks.\r\n0\r\n\r\n"],
                "Wi\r\nkipedia in\r\n\r\nchunks."
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideChunkedEncoding
     */
    public function testChunkedEncoding(array $strings, $expected = "Wikipedia in\r\n\r\nchunks.")
    {
        $stream = new ThroughStream();
        $response = new ChunkedStreamDecoder($stream);
        $buffer = '';
        $response->on('data', function ($data) use (&$buffer) {
            $buffer .= $data;
        });
        $response->on('error', function (Exception $exception) {
            throw $exception;
        });
        foreach ($strings as $string) {
            $stream->write($string);
        }
        $this->assertSame($expected, $buffer);
    }

    public function provideInvalidChunkedEncoding()
    {
        return [
            [
                ["4\r\nWiwot40n98w3498tw3049nyn039409t34\r\n", "ki\r\n5\r\npedia\r\ne\r\n in\r\n\r\nchunks.\r\n0\r\n\r\n"],
            ],
            [
                str_split("xyz\r\nWi\r\nki\r\n5\r\npedia\r\ne\r\n in\r\n\r\nchunks.\r\n0\r\n\r\n")
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideInvalidChunkedEncoding
     * @expectedException Exception
     */
    public function testInvalidChunkedEncoding(array $strings)
    {
        $stream = new ThroughStream();
        $response = new ChunkedStreamDecoder($stream);
        $response->on('error', function (Exception $exception) {
            throw $exception;
        });
        foreach ($strings as $string) {
            $stream->write($string);
        }
    }
}
