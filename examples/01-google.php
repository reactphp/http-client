<?php

use React\HttpClient\Factory;
use React\HttpClient\Response;

require __DIR__ . '/../vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

$resolverFactory = new React\Dns\Resolver\Factory();
$resolver = $resolverFactory->create('8.8.8.8', $loop);

$factory = new Factory();
$client = $factory->create($loop, $resolver);

$request = $client->request('GET', 'https://google.com/');

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
