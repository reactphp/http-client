<?php

namespace React\Tests\HttpClient;

use React\HttpClient\ChunkedStreamDecoder;
use React\Stream\ThroughStream;

class DecodeChunkedStreamTest extends TestCase
{
    public function provideChunkedEncoding()
    {
        return [
            [
                ["4\r\nWiki\r\n5\r\npedia\r\ne\r\n in\r\n\r\nchunks.\r\n0\r\n\r\n"],
                [
                    '',
                    '',
                    '',
                ],
            ],
            [
                ["4\r\nWiki\r\n", "5\r\npedia\r\ne\r\n in\r\n\r\nchunks.\r\n0\r\n\r\n"],
                [
                    '',
                    '',
                    '',
                ],
            ],
            [
                ["4\r\nWiki\r\n", "5\r\n", "pedia\r\ne\r\n in\r\n\r\nchunks.\r\n0\r\n\r\n"],
                [

                    '',
                    '',
                    '',
                ],
            ],
            [
                ["4\r\nWiki\r\n", "5\r\n", "pedia\r\ne\r\n in\r\n", "\r\nchunks.\r\n0\r\n\r\n"],
                [
                    '',
                    '',
                    '',
                    '',
                ],
            ],
            [
                ["4\r\n", "Wiki\r\n", "5\r\n", "pedia\r\ne\r\n in\r\n", "\r\nchunks.\r\n0\r\n\r\n"],
                [
                    '',
                    '',
                    '',
                    '',
                ],
            ],
            [
                ["4\r\n", "Wiki\r\n", "5\r\n", "pedia\r\ne; foo=[bar,beer,pool,cue,win,won]\r\n", " in\r\n", "\r\nchunks.\r\n0\r\n\r\n"],
                [
                    '',
                    '',
                    'foo=[bar,beer,pool,cue,win,won]',
                    'foo=[bar,beer,pool,cue,win,won]',
                ],
            ],
            [
                ["4; foo=bar\r\n", "Wiki\r\n", "5\r\n", "pedia\r\ne\r\n", " in\r\n", "\r\nchunks.\r\n", "0\r\n\r\n"],
                [
                    'foo=bar',
                    '',
                    '',
                    '',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideChunkedEncoding
     */
    public function testChunkedEncoding(array $strings, array $extensions)
    {
        $stream = new ThroughStream();
        $response = new ChunkedStreamDecoder($stream);
        $buffer = '';
        $exts = [];
        $response->on('data', function ($data, $response, $ext) use (&$buffer, &$exts) {
            $buffer .= $data;
            $exts[] = $ext;
        });
        foreach ($strings as $string) {
            $stream->write($string);
        }
        $this->assertSame("Wikipedia in\r\n\r\nchunks.", $buffer);
        $this->assertSame($extensions, $exts);
    }
}
