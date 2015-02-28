<?php

namespace React\Tests\HttpClient;

use React\HttpClient\ConnectorPair;
use React\HttpClient\Request;
use React\HttpClient\RequestData;
use React\HttpClient\RequestOptions;
use React\Stream\Stream;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;

class RequestTest extends TestCase
{
    private $connector;
    private $stream;

    public function setUp()
    {
        $this->stream = $this->getMockBuilder('React\Stream\Stream')
            ->disableOriginalConstructor()
            ->getMock();

        $this->connector = $this->getMock('React\SocketClient\ConnectorInterface');

        $this->response = $this->getMockBuilder('React\HttpClient\Response')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /** @test */
    public function requestShouldBindToStreamEventsAndUseconnector()
    {
        $requestData = new RequestData('GET', 'http://www.example.com');
        $requestOptions = new RequestOptions();
        $connectorPair = new ConnectorPair($this->connector, $this->connector);
        $request = new Request($connectorPair, $requestData, $requestOptions);

        $this->successfulConnectionMock();

        $this->stream
            ->expects($this->at(0))
            ->method('on')
            ->with('drain', $this->identicalTo(array($request, 'handleDrain')));
        $this->stream
            ->expects($this->at(1))
            ->method('on')
            ->with('data', $this->identicalTo(array($request, 'handleData')));
        $this->stream
            ->expects($this->at(2))
            ->method('on')
            ->with('end', $this->identicalTo(array($request, 'handleEnd')));
        $this->stream
            ->expects($this->at(3))
            ->method('on')
            ->with('error', $this->identicalTo(array($request, 'handleError')));
        $this->stream
            ->expects($this->at(5))
            ->method('removeListener')
            ->with('drain', $this->identicalTo(array($request, 'handleDrain')));
        $this->stream
            ->expects($this->at(6))
            ->method('removeListener')
            ->with('data', $this->identicalTo(array($request, 'handleData')));
        $this->stream
            ->expects($this->at(7))
            ->method('removeListener')
            ->with('end', $this->identicalTo(array($request, 'handleEnd')));
        $this->stream
            ->expects($this->at(8))
            ->method('removeListener')
            ->with('error', $this->identicalTo(array($request, 'handleError')));

        $response = $this->response;

        $response->expects($this->once())
            ->method('emit')
            ->with('data', array('body'));

        $response->expects($this->at(0))
            ->method('getCode')
            ->will($this->returnValue(200));

        $response->expects($this->at(1))
            ->method('on')
            ->with('end', $this->anything())
            ->will($this->returnCallback(function ($event, $cb) use (&$endCallback) {
                $endCallback = $cb;
            }));

        $factory = $this->createCallableMock();
        $factory->expects($this->once())
            ->method('__invoke')
            ->with('HTTP', '1.0', '200', 'OK', array('Content-Type' => 'text/plain'))
            ->will($this->returnValue($response));

        $request->setResponseFactory($factory);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with($response);

        $request->on('response', $handler);
        $request->on('close', $this->expectCallableNever());

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                null,
                $this->isInstanceof('React\HttpClient\Response'),
                $this->isInstanceof('React\HttpClient\Request')
            );

        $request->on('end', $handler);
        $request->end();

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");

        $this->assertNotNull($endCallback);
        call_user_func($endCallback);
    }

    /** @test */
    public function requestShouldEmitErrorIfConnectionFails()
    {
        $requestData = new RequestData('GET', 'http://www.example.com');
        $requestOptions = new RequestOptions();
        $connectorPair = new ConnectorPair($this->connector, $this->connector);
        $request = new Request($connectorPair, $requestData, $requestOptions);

        $this->rejectedConnectionMock();

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('RuntimeException'),
                $this->isInstanceOf('React\HttpClient\Request')
            );

        $request->on('error', $handler);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('RuntimeException'),
                null,
                $this->isInstanceOf('React\HttpClient\Request')
            );

        $request->on('end', $handler);
        $request->on('close', $this->expectCallableNever());

        $request->end();
    }

    /** @test */
    public function requestShouldEmitErrorIfConnectionEndsBeforeResponseIsParsed()
    {
        $requestData = new RequestData('GET', 'http://www.example.com');
        $requestOptions = new RequestOptions();
        $connectorPair = new ConnectorPair($this->connector, $this->connector);
        $request = new Request($connectorPair, $requestData, $requestOptions);

        $this->successfulConnectionMock();

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('RuntimeException'),
                $this->isInstanceOf('React\HttpClient\Request')
            );

        $request->on('error', $handler);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('RuntimeException'),
                null,
                $this->isInstanceOf('React\HttpClient\Request')
            );

        $request->on('end', $handler);
        $request->on('close', $this->expectCallableNever());

        $request->end();
        $request->handleEnd();
    }

    /** @test */
    public function requestShouldEmitErrorIfConnectionEmitsError()
    {
        $requestData = new RequestData('GET', 'http://www.example.com');
        $requestOptions = new RequestOptions();
        $connectorPair = new ConnectorPair($this->connector, $this->connector);
        $request = new Request($connectorPair, $requestData, $requestOptions);

        $this->successfulConnectionMock();

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('Exception'),
                $this->isInstanceOf('React\HttpClient\Request')
            );

        $request->on('error', $handler);

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('Exception'),
                null,
                $this->isInstanceOf('React\HttpClient\Request')
            );

        $request->on('end', $handler);
        $request->on('close', $this->expectCallableNever());

        $request->end();
        $request->handleError(new \Exception('test'));
    }

    /** @test */
    public function postRequestShouldSendAPostRequest()
    {
        $requestData = new RequestData('POST', 'http://www.example.com');
        $requestOptions = new RequestOptions();
        $connectorPair = new ConnectorPair($this->connector, $this->connector);
        $request = new Request($connectorPair, $requestData, $requestOptions);

        $this->successfulConnectionMock();

        $this->stream
            ->expects($this->at(4))
            ->method('write')
            ->with($this->matchesRegularExpression("#^POST / HTTP/1\.0\r\nHost: www.example.com\r\nUser-Agent:.*\r\n\r\n$#"));
        $this->stream
            ->expects($this->at(5))
            ->method('write')
            ->with($this->identicalTo("some post data"));

        $factory = $this->createCallableMock();
        $factory->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($this->response));

        $request->setResponseFactory($factory);
        $request->end('some post data');

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");
    }

    /** @test */
    public function redirectingPostRequestShouldSwitchToGet()
    {
        $requestData = new RequestData('POST', 'http://www.example.com');
        $requestOptions = new RequestOptions([
            'followRedirects' => true,
            'maxRedirects' => 1,
        ]);
        $connectorPair = new ConnectorPair($this->connector, $this->connector);
        $request = new Request($connectorPair, $requestData, $requestOptions);

        $this->connector
            ->expects($this->at(0))
            ->method('create')
            ->with('www.example.com', 80)
            ->will($this->returnValue(new FulfilledPromise($this->stream)));

        $secondStream = $this->getMockBuilder('React\Stream\Stream')
            ->disableOriginalConstructor()
            ->getMock();

        $secondStream
            ->expects($this->at(4))
            ->method('write')
            ->with($this->matchesRegularExpression("#^GET / HTTP/1\.0\r\nHost: www.foo.bar\r\nUser-Agent:.*\r\n\r\n$#"));
        $secondStream
            ->expects($this->at(5))
            ->method('close');

        $this->connector
            ->expects($this->at(1))
            ->method('create')
            ->with('www.foo.bar', 80)
            ->will($this->returnValue(new FulfilledPromise($secondStream)));

        $this->stream
            ->expects($this->at(4))
            ->method('write')
            ->with($this->matchesRegularExpression("#^POST / HTTP/1\.0\r\nHost: www.example.com\r\nUser-Agent:.*\r\n\r\n$#"));
        $this->stream
            ->expects($this->at(5))
            ->method('write')
            ->with($this->identicalTo("some post data"));
        $this->stream
            ->expects($this->at(6))
            ->method('close');

        $factory = $this->createCallableMock();
        $factory->expects($this->at(0))
            ->method('__invoke')
            ->will($this->returnValue($this->response));

        $this->response
            ->expects($this->exactly(2))
            ->method('getCode')
            ->will($this->returnValue(302));

        $this->response
            ->expects($this->exactly(1))
            ->method('getHeaders')
            ->will($this->returnValue([
                'Location' => 'http://www.foo.bar',
                'Content-Type' => 'text/plain',
            ]));

        $request->setResponseFactory($factory);
        $request->end('some post data');

        $request->handleData("HTTP/1.0 302 Found\r\n");
        $request->handleData("Location: http://www.foo.bar\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");
    }

    /** @test */
    public function shouldDetectCyclicRedirect()
    {
        $requestData = new RequestData('GET', 'http://www.example.com');
        $requestOptions = new RequestOptions([
            'followRedirects' => true,
            'maxRedirects' => 5,
        ]);
        $connectorPair = new ConnectorPair($this->connector, $this->connector);
        $request = new Request($connectorPair, $requestData, $requestOptions);

        $this->connector
            ->expects($this->at(0))
            ->method('create')
            ->with('www.example.com', 80)
            ->will($this->returnValue(new FulfilledPromise($this->stream)));

        $this->stream
            ->expects($this->at(4))
            ->method('write')
            ->with($this->matchesRegularExpression("#^GET / HTTP/1\.0\r\nHost: www.example.com\r\nUser-Agent:.*\r\n\r\n$#"));
        $this->stream
            ->expects($this->at(5))
            ->method('close');

        $factory = $this->createCallableMock();
        $factory->expects($this->at(0))
            ->method('__invoke')
            ->will($this->returnValue($this->response));

        $this->response
            ->expects($this->exactly(2))
            ->method('getCode')
            ->will($this->returnValue(302));

        $this->response
            ->expects($this->exactly(1))
            ->method('getHeaders')
            ->will($this->returnValue([
                'Location' => 'http://www.example.com',
                'Content-Type' => 'text/plain',
            ]));

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('RuntimeException'),
                null,
                $this->isInstanceOf('React\HttpClient\Request')
            );

        $request->on('end', $handler);
        $request->on('close', $this->expectCallableNever());

        $request->setResponseFactory($factory);
        $request->end();

        $request->handleData("HTTP/1.0 302 Found\r\n");
        $request->handleData("Location: http://www.example.com\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");
    }

    /** @test */
    public function shouldAllowRedirectFromPostToGet()
    {
        $requestData = new RequestData('POST', 'http://www.example.com');
        $requestOptions = new RequestOptions([
            'followRedirects' => true,
            'maxRedirects' => 1,
        ]);
        $connectorPair = new ConnectorPair($this->connector, $this->connector);
        $request = new Request($connectorPair, $requestData, $requestOptions);

        $this->connector
            ->expects($this->at(0))
            ->method('create')
            ->with('www.example.com', 80)
            ->will($this->returnValue(new FulfilledPromise($this->stream)));

        $secondStream = $this->getMockBuilder('React\Stream\Stream')
            ->disableOriginalConstructor()
            ->getMock();

        $secondStream
            ->expects($this->at(4))
            ->method('write')
            ->with($this->matchesRegularExpression("#^GET / HTTP/1\.0\r\nHost: www.example.com\r\nUser-Agent:.*\r\n\r\n$#"));
        $secondStream
            ->expects($this->at(5))
            ->method('close');

        $this->connector
            ->expects($this->at(1))
            ->method('create')
            ->with('www.example.com', 80)
            ->will($this->returnValue(new FulfilledPromise($secondStream)));

        $this->stream
            ->expects($this->at(4))
            ->method('write')
            ->with($this->matchesRegularExpression("#^POST / HTTP/1\.0\r\nHost: www.example.com\r\nUser-Agent:.*\r\n\r\n$#"));
        $this->stream
            ->expects($this->at(5))
            ->method('write')
            ->with($this->identicalTo("some post data"));
        $this->stream
            ->expects($this->at(6))
            ->method('close');

        $factory = $this->createCallableMock();
        $factory->expects($this->exactly(1))
            ->method('__invoke')
            ->will($this->returnValue($this->response));

        $this->response
            ->expects($this->exactly(2))
            ->method('getCode')
            ->will($this->returnValue(302));

        $this->response
            ->expects($this->exactly(1))
            ->method('getHeaders')
            ->will($this->returnValue([
                'Location' => 'http://www.example.com',
                'Content-Type' => 'text/plain',
            ]));

        $request->setResponseFactory($factory);
        $request->end('some post data');

        $request->handleData("HTTP/1.0 302 Found\r\n");
        $request->handleData("Location: http://www.example.com\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");
    }

    /** @test */
    public function shouldDetectTooManyRedirects()
    {
        $requestData = new RequestData('GET', 'http://www.example.com');
        $requestOptions = new RequestOptions([
            'followRedirects' => true,
            'maxRedirects' => 0,
        ]);
        $connectorPair = new ConnectorPair($this->connector, $this->connector);
        $request = new Request($connectorPair, $requestData, $requestOptions);

        $this->connector
            ->expects($this->at(0))
            ->method('create')
            ->with('www.example.com', 80)
            ->will($this->returnValue(new FulfilledPromise($this->stream)));

        $this->stream
            ->expects($this->at(4))
            ->method('write')
            ->with($this->matchesRegularExpression("#^GET / HTTP/1\.0\r\nHost: www.example.com\r\nUser-Agent:.*\r\n\r\n$#"));
        $this->stream
            ->expects($this->at(5))
            ->method('close');

        $factory = $this->createCallableMock();
        $factory->expects($this->at(0))
            ->method('__invoke')
            ->will($this->returnValue($this->response));

        $this->response
            ->expects($this->exactly(1))
            ->method('getCode')
            ->will($this->returnValue(302));

        $handler = $this->createCallableMock();
        $handler->expects($this->once())
            ->method('__invoke')
            ->with(
                $this->isInstanceOf('RuntimeException'),
                null,
                $this->isInstanceOf('React\HttpClient\Request')
            );

        $request->on('end', $handler);
        $request->on('close', $this->expectCallableNever());

        $request->setResponseFactory($factory);
        $request->end();

        $request->handleData("HTTP/1.0 302 Found\r\n");
        $request->handleData("Location: http://www.foo.bar\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");
    }

    /** @test */
    public function writeWithAPostRequestShouldSendToTheStream()
    {
        $requestData = new RequestData('POST', 'http://www.example.com');
        $requestOptions = new RequestOptions();
        $connectorPair = new ConnectorPair($this->connector, $this->connector);
        $request = new Request($connectorPair, $requestData, $requestOptions);

        $this->successfulConnectionMock();

        $this->stream
            ->expects($this->at(4))
            ->method('write')
            ->with($this->matchesRegularExpression("#^POST / HTTP/1\.0\r\nHost: www.example.com\r\nUser-Agent:.*\r\n\r\n$#"));
        $this->stream
            ->expects($this->at(5))
            ->method('write')
            ->with($this->identicalTo("some"));
        $this->stream
            ->expects($this->at(6))
            ->method('write')
            ->with($this->identicalTo("post"));
        $this->stream
            ->expects($this->at(7))
            ->method('write')
            ->with($this->identicalTo("data"));

        $factory = $this->createCallableMock();
        $factory->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($this->response));

        $request->setResponseFactory($factory);

        $request->write("some");
        $request->write("post");
        $request->end("data");

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");
    }

    /** @test */
    public function pipeShouldPipeDataIntoTheRequestBody()
    {
        $requestData = new RequestData('POST', 'http://www.example.com');
        $requestOptions = new RequestOptions();
        $connectorPair = new ConnectorPair($this->connector, $this->connector);
        $request = new Request($connectorPair, $requestData, $requestOptions);

        $this->successfulConnectionMock();

        $this->stream
            ->expects($this->at(4))
            ->method('write')
            ->with($this->matchesRegularExpression("#^POST / HTTP/1\.0\r\nHost: www.example.com\r\nUser-Agent:.*\r\n\r\n$#"));
        $this->stream
            ->expects($this->at(5))
            ->method('write')
            ->with($this->identicalTo("some"));
        $this->stream
            ->expects($this->at(6))
            ->method('write')
            ->with($this->identicalTo("post"));
        $this->stream
            ->expects($this->at(7))
            ->method('write')
            ->with($this->identicalTo("data"));

        $factory = $this->createCallableMock();
        $factory->expects($this->once())
            ->method('__invoke')
            ->will($this->returnValue($this->response));

        $loop = $this->getMock('React\EventLoop\LoopInterface');

        $request->setResponseFactory($factory);

        $stream = fopen('php://memory', 'r+');
        $stream = new Stream($stream, $loop);

        $stream->pipe($request);
        $stream->emit('data', array('some'));
        $stream->emit('data', array('post'));
        $stream->emit('data', array('data'));

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage $data must be null or scalar
     */
    public function endShouldOnlyAcceptScalars()
    {
        $requestData = new RequestData('POST', 'http://www.example.com');
        $requestOptions = new RequestOptions();
        $connectorPair = new ConnectorPair($this->connector, $this->connector);
        $request = new Request($connectorPair, $requestData, $requestOptions);

        $request->end(array());
    }

    /** @test */
    public function requestShouldRelayErrorEventsFromResponse()
    {
        $requestData = new RequestData('GET', 'http://www.example.com');
        $requestOptions = new RequestOptions();
        $connectorPair = new ConnectorPair($this->connector, $this->connector);
        $request = new Request($connectorPair, $requestData, $requestOptions);

        $this->successfulConnectionMock();

        $response = $this->response;

        $response->expects($this->at(0))
            ->method('getCode')
            ->will($this->returnValue(200));

        $response->expects($this->at(1))
            ->method('on')
            ->with('end', $this->anything());

        $response->expects($this->at(2))
            ->method('on')
            ->with('error', $this->anything())
            ->will($this->returnCallback(function ($event, $cb) use (&$errorCallback) {
                $errorCallback = $cb;
            }));

        $factory = $this->createCallableMock();
        $factory->expects($this->once())
            ->method('__invoke')
            ->with('HTTP', '1.0', '200', 'OK', array('Content-Type' => 'text/plain'))
            ->will($this->returnValue($response));

        $request->setResponseFactory($factory);
        $request->end();

        $request->handleData("HTTP/1.0 200 OK\r\n");
        $request->handleData("Content-Type: text/plain\r\n");
        $request->handleData("\r\nbody");

        $this->assertNotNull($errorCallback);
        call_user_func($errorCallback, new \Exception('test'));
    }

    private function successfulConnectionMock()
    {
        $this->connector
            ->expects($this->once())
            ->method('create')
            ->with('www.example.com', 80)
            ->will($this->returnValue(new FulfilledPromise($this->stream)));
    }

    private function rejectedConnectionMock()
    {
        $this->connector
            ->expects($this->once())
            ->method('create')
            ->with('www.example.com', 80)
            ->will($this->returnValue(new RejectedPromise(new \RuntimeException())));
    }
}
