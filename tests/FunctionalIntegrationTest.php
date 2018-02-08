<?php

namespace React\Tests\HttpClient;

use React\EventLoop\Factory;
use React\HttpClient\Client;
use React\HttpClient\Response;
use React\Socket\Server;
use React\Socket\ConnectionInterface;

class FunctionalIntegrationTest extends TestCase
{
    public function testRequestToLocalhostEmitsSingleRemoteConnection()
    {
        $loop = Factory::create();

        $server = new Server(0, $loop);
        $server->on('connection', $this->expectCallableOnce());
        $server->on('connection', function (ConnectionInterface $conn) use ($server) {
            $conn->end("HTTP/1.1 200 OK\r\n\r\nOk");
            $server->close();
        });
        $port = parse_url($server->getAddress(), PHP_URL_PORT);

        $client = new Client($loop);
        $request = $client->request('GET', 'http://localhost:' . $port);
        $request->end();

        $loop->run();
    }

    /** @group internet */
    public function testSuccessfulResponseEmitsEnd()
    {
        $loop = Factory::create();
        $client = new Client($loop);

        $request = $client->request('GET', 'http://www.google.com/');

        $once = $this->expectCallableOnce();
        $request->on('response', function (Response $response) use ($once) {
            $response->on('end', $once);
        });

        $request->end();

        $loop->run();
    }

    /** @group internet */
    public function testPostDataReturnsData()
    {
        $loop = Factory::create();
        $client = new Client($loop);

        $data = str_repeat('.', 33000);
        $request = $client->request('POST', 'https://' . (mt_rand(0, 1) === 0 ? 'eu.' : '') . 'httpbin.org/post', array('Content-Length' => strlen($data)));

        $buffer = '';
        $request->on('response', function (Response $response) use (&$buffer) {
            $response->on('data', function ($chunk) use (&$buffer) {
                $buffer .= $chunk;
            });
        });

        $request->on('error', 'printf');
        $request->on('error', $this->expectCallableNever());

        $request->end($data);

        $loop->run();

        $this->assertNotEquals('', $buffer);

        $parsed = json_decode($buffer, true);
        $this->assertTrue(is_array($parsed) && isset($parsed['data']));
        $this->assertEquals(strlen($data), strlen($parsed['data']));
        $this->assertEquals($data, $parsed['data']);
    }

    /** @group internet */
    public function testPostJsonReturnsData()
    {
        $loop = Factory::create();
        $client = new Client($loop);

        $data = json_encode(array('numbers' => range(1, 50)));
        $request = $client->request('POST', 'https://httpbin.org/post', array('Content-Length' => strlen($data), 'Content-Type' => 'application/json'));

        $buffer = '';
        $request->on('response', function (Response $response) use (&$buffer) {
            $response->on('data', function ($chunk) use (&$buffer) {
                $buffer .= $chunk;
            });
        });

        $request->on('error', 'printf');
        $request->on('error', $this->expectCallableNever());

        $request->end($data);

        $loop->run();

        $this->assertNotEquals('', $buffer);

        $parsed = json_decode($buffer, true);
        $this->assertTrue(is_array($parsed) && isset($parsed['json']));
        $this->assertEquals(json_decode($data, true), $parsed['json']);
    }

    /** @group internet */
    public function testCancelPendingConnectionEmitsClose()
    {
        $loop = Factory::create();
        $client = new Client($loop);

        $request = $client->request('GET', 'http://www.google.com/');
        $request->on('error', $this->expectCallableNever());
        $request->on('close', $this->expectCallableOnce());
        $request->end();
        $request->close();

        $loop->run();
    }
}
