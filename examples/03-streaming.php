<?php

use React\HttpClient\Client;
use React\HttpClient\Response;

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();
$client = new Client($loop);

$request = $client->request('GET', 'http://httpbin.org/drip?duration=5&numbytes=5&code=200');

$request->on('response', function (Response $response) {
    var_dump($response->getHeaders());

    $response->on('data', function ($chunk) {
        echo $chunk;
    });

    $response->on('end', function () {
        echo 'DONE' . PHP_EOL;
    });
});

$request->end();

$loop->run();
