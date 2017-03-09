# HttpClient Component

[![Build Status](https://secure.travis-ci.org/reactphp/http-client.png?branch=master)](http://travis-ci.org/reactphp/http-client) [![Code Climate](https://codeclimate.com/github/reactphp/http-client/badges/gpa.svg)](https://codeclimate.com/github/reactphp/http-client)

Basic HTTP/1.0 client.

## Basic usage

Requests are prepared using the ``Client#request()`` method. Body can be
sent with ``Request#write()``. ``Request#end()`` finishes sending the request
(or sends it at all if no body was written).

Request implements WritableStreamInterface, so a Stream can be piped to
it. Response implements ReadableStreamInterface.

Interesting events emitted by Request:

* `response`: The response headers were received from the server and successfully
  parsed. The first argument is a Response instance.
* `error`: An error occurred.
* `end`: The request is finished. If an error occurred, it is passed as first
  argument. Second and third arguments are the Response and the Request.

Interesting events emitted by Response:

* `data`: Passes a chunk of the response body as first argument and a Response
  object itself as second argument. When a response encounters a chunked encoded response it will parse it transparently for the user of `Response` and removing the `Transfer-Encoding` header.
* `error`: An error occurred.
* `end`: The response has been fully received. If an error
  occurred, it is passed as first argument.

### Example

```php
<?php

$loop = React\EventLoop\Factory::create();

$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);

$factory = new React\HttpClient\Factory();
$client = $factory->create($loop, $dnsResolver);

$request = $client->request('GET', 'https://github.com/');
$request->on('response', function ($response) {
    $response->on('data', function ($data, $response) {
        // ...
    });
});
$request->end();
$loop->run();
```

See also the [examples](examples).

## TODO

* gzip content encoding
* keep-alive connections
* following redirections

## Tests

To run the test suite, you first need to clone this repo and then install all
dependencies [through Composer](https://getcomposer.org):

```bash
$ composer install
```

To run the test suite, go to the project root and run:

```bash
$ php vendor/bin/phpunit
```
