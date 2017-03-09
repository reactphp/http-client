<?php

namespace React\Tests\HttpClient;

use React\HttpClient\Response;
use React\Stream\ThroughStream;

class ResponseTest extends TestCase
{
    private $stream;

    public function setUp()
    {
        $this->stream = $this->getMockBuilder('React\Stream\Stream')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /** @test */
    public function responseShouldEmitEndEventOnEnd()
    {
        $this->stream
            ->expects($this->at(0))
            ->method('on')
            ->with('data', $this->anything());
        $this->stream
            ->expects($this->at(1))
            ->method('on')
            ->with('error', $this->anything());
        $this->stream
            ->expects($this->at(2))
            ->method('on')
            ->with('end', $this->anything());

        $response = new Response($this->stream, 'HTTP', '1.0', '200', 'OK', array('Content-Type' => 'text/plain'));

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with('some data', $this->anything());

        $response->on('data', $handler);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(null, $this->isInstanceOf('React\HttpClient\Response'));

        $response->on('end', $handler);
        $response->on('close', $this->expectCallableNever());

        $this->stream
            ->expects($this->at(0))
            ->method('close');

        $response->handleData('some data');
        $response->handleEnd();

        $this->assertSame(
            [
                'Content-Type' => 'text/plain'
            ],
            $response->getHeaders()
        );
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

