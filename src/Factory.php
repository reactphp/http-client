<?php

namespace React\HttpClient;

use React\EventLoop\LoopInterface;
use React\Dns\Resolver\Resolver;
use React\Socket\Connector;

class Factory
{
    public function create(LoopInterface $loop, Resolver $resolver)
    {
        $connector = new Connector($loop, array(
            'dns' => $resolver
        ));

        return new Client($connector);
    }
}
