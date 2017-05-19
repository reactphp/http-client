<?php

namespace React\Tests\HttpClient;

use React\EventLoop\Factory;
use React\HttpClient\Client;
use React\HttpClient\Response;

/** @group internet */
class FunctionalIntegrationTest extends TestCase
{
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
