<?php

namespace React\Tests\HttpClient;

use React\HttpClient\Response;
use React\Stream\ThroughStream;

class ResponseTest extends TestCase
{
    private $stream;

    public function setUp()
    {
        $this->stream = $this->getMockbuilder('React\Stream\Stream')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /** @test */
    public function closedResponseShouldNotBeResumedOrPaused()
    {
        $response = new Response($this->stream, 'http', '1.0', '200', 'ok', array('content-type' => 'text/plain'));

        $this->stream
            ->expects($this->never())
            ->method('pause');
        $this->stream
            ->expects($this->never())
            ->method('resume');

        $response->handleEnd();

        $response->resume();
        $response->pause();

        $this->assertSame(
            [
                'content-type' => 'text/plain',
            ],
            $response->getHeaders()
        );
    }

    /** @test */
    public function chunkedEncodingResponse()
    {
        $stream = new ThroughStream();
        $response = new Response(
            $stream,
            'http',
            '1.0',
            '200',
            'ok',
            [
                'content-type' => 'text/plain',
                'transfer-encoding' => 'chunked',
            ]
        );

        $buffer = '';
        $response->on('data', function ($data, $stream) use (&$buffer) {
            $buffer.= $data;
        });
        $this->assertSame('', $buffer);
        $stream->write("4; abc=def\r\n");
        $this->assertSame('', $buffer);
        $stream->write("Wiki\r\n");
        $this->assertSame('Wiki', $buffer);

        $this->assertSame(
            [
                'content-type' => 'text/plain',
            ],
            $response->getHeaders()
        );
    }
}

