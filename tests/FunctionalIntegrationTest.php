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
