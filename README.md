# HttpClient Component

[![Build Status](https://secure.travis-ci.org/reactphp/http-client.png?branch=master)](http://travis-ci.org/reactphp/http-client) [![Code Climate](https://codeclimate.com/github/reactphp/http-client/badges/gpa.svg)](https://codeclimate.com/github/reactphp/http-client)

Basic HTTP/1.0 client.

## Basic usage

Requests are prepared using the ``Client#request()`` method.

The `Request#write(string $data)` method can be used to
write data to the request body.
Data will be buffered until the underlying connection is established, at which
point buffered data will be sent and all further data will be passed to the
underlying connection immediately.

The `Request#end(?string $data = null)` method can be used to
finish sending the request.
You may optionally pass a last request body data chunk that will be sent just
like a `write()` call.
Calling this method finalizes the outgoing request body (which may be empty).
Data will be buffered until the underlying connection is established, at which
point buffered data will be sent and all further data will be ignored.

Request implements WritableStreamInterface, so a Stream can be piped to it.
Interesting events emitted by Request:

* `response`: The response headers were received from the server and successfully
  parsed. The first argument is a Response instance.
* `drain`: The outgoing buffer drained and the response is ready to accept more
  data for the next `write()` call.
* `error`: An error occurred.
* `end`: The request is finished. If an error occurred, it is passed as first
  argument. Second and third arguments are the Response and the Request.

Response implements ReadableStreamInterface.
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
$client = new React\HttpClient\Client($loop);

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
