<?php

namespace React\Tests\HttpClient;

use GuzzleHttp\Psr7\Response as psr7Response;
use React\HttpClient\StreamDecoder;
use React\Stream\ThroughStream;

class StreamDecoderTest extends TestCase
{
    public function providerDetect()
    {
        return [
            [
                new psr7Response(200),
                'React\Stream\ThroughStream',
            ],
            [
                new psr7Response(200, [
                    'Content-Encoding' => [
                        'gzip'
                    ],
                ]),
                'Clue\React\Zlib\ZlibFilterStream',
            ],
            [
                new psr7Response(200, [
                    'Content-Encoding' => [
                        'deflate'
                    ],
                ]),
                'Clue\React\Zlib\ZlibFilterStream',
            ],
            [
                new psr7Response(200, [
                    'Content-Encoding' => [
                        'foobar'
                    ],
                ]),
                'React\Stream\ThroughStream',
            ],
        ];
    }

    /**
     * @dataProvider providerDetect
     */
    public function testDetect($response, $instanceOf)
    {
        $stream = StreamDecoder::detect(new ThroughStream(), $response);
        $this->assertInstanceOf($instanceOf, $stream);
    }
}
