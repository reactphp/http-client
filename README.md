# HttpClient

[![Build Status](https://travis-ci.org/reactphp/http-client.svg?branch=master)](https://travis-ci.org/reactphp/http-client)

Event-driven, streaming HTTP client for [ReactPHP](https://reactphp.org).

> Note that this is a very low-level HTTP client implementation that is currently
  undergoing some major changes. In the meantime, we recommend using
  [clue/reactphp-buzz](https://github.com/clue/reactphp-buzz) as a higher-level
  HTTP client abstraction (which happens to build on top of this project). It
  provides a Promise-based interface and common PSR-7 message abstraction which
  makes getting started much easier.

**Table of Contents**

* [Basic usage](#basic-usage)
  * [Client](#client)
  * [Example](#example)
* [Advanced usage](#advanced-usage)
  * [Unix domain sockets](#unix-domain-sockets)
* [Install](#install)
* [Tests](#tests)
* [License](#license)

## Basic usage

### Client

The `Client` is responsible for communicating with HTTP servers, managing the
connection state and sending your HTTP requests.
It also registers everything with the main [`EventLoop`](https://github.com/reactphp/event-loop#usage).

```php
$loop = React\EventLoop\Factory::create();
$client = new Client($loop);
```

If you need custom connector settings (DNS resolution, TLS parameters, timeouts,
proxy servers etc.), you can explicitly pass a custom instance of the
[`ConnectorInterface`](https://github.com/reactphp/socket#connectorinterface):

```php
$connector = new \React\Socket\Connector($loop, array(
    'dns' => '127.0.0.1',
    'tcp' => array(
        'bindto' => '192.168.10.1:0'
    ),
    'tls' => array(
        'verify_peer' => false,
        'verify_peer_name' => false
    )
));

$client = new Client($loop, $connector);
```

The `request(string $method, string $uri, array $headers = array(), string $version = '1.0'): Request`
method can be used to prepare new Request objects.

The optional `$headers` parameter can be used to pass additional request
headers.
You can use an associative array (key=value) or an array for each header value
(key=values).
The Request will automatically include an appropriate `Host`,
`User-Agent: react/alpha` and `Connection: close` header if applicable.
You can pass custom header values or use an empty array to omit any of these.

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

The `Request#close()` method can be used to
forefully close sending the request.
Unlike the `end()` method, this method discards any buffers and closes the
underlying connection if it is already established or cancels the pending
connection attempt otherwise.

Request implements WritableStreamInterface, so a Stream can be piped to it.
Interesting events emitted by Request:

* `response`: The response headers were received from the server and successfully
  parsed. The first argument is a Response instance.
* `drain`: The outgoing buffer drained and the response is ready to accept more
  data for the next `write()` call.
* `error`: An error occurred, an `Exception` is passed as first argument.
  If the response emits an `error` event, this will also be emitted here.
* `close`: The request is closed. If an error occurred, this event will be
  preceeded by an `error` event.
  For a successful response, this will be emitted only once the response emits
  the `close` event.

Response implements ReadableStreamInterface.
Interesting events emitted by Response:

* `data`: Passes a chunk of the response body as first argument.
  When a response encounters a chunked encoded response it will parse it
  transparently for the user and removing the `Transfer-Encoding` header.
* `error`: An error occurred, an `Exception` is passed as first argument.
  This will also be forwarded to the request and emit an `error` event there.
* `end`: The response has been fully received.
* `close`: The response is closed. If an error occured, this event will be
  preceeded by an `error` event.
  This will also be forwarded to the request and emit a `close` event there.

### Example

```php
<?php

$loop = React\EventLoop\Factory::create();
$client = new React\HttpClient\Client($loop);

$request = $client->request('GET', 'https://github.com/');
$request->on('response', function ($response) {
    $response->on('data', function ($chunk) {
        echo $chunk;
    });
    $response->on('end', function() {
        echo 'DONE';
    });
});
$request->on('error', function (\Exception $e) {
    echo $e;
});
$request->end();
$loop->run();
```

See also the [examples](examples).

## Advanced Usage

### Unix domain sockets

By default, this library supports transport over plaintext TCP/IP and secure
TLS connections for the `http://` and `https://` URI schemes respectively.
This library also supports Unix domain sockets (UDS) when explicitly configured.

In order to use a UDS path, you have to explicitly configure the connector to
override the destination URI so that the hostname given in the request URI will
no longer be used to establish the connection:

```php
$connector = new FixedUriConnector(
    'unix:///var/run/docker.sock',
    new UnixConnector($loop)
);

$client = new Client($loop, $connector);

$request = $client->request('GET', 'http://localhost/info');
```

See also [example #11](examples/11-unix-domain-sockets.php).

## Install

The recommended way to install this library is [through Composer](https://getcomposer.org).
[New to Composer?](https://getcomposer.org/doc/00-intro.md)

This will install the latest supported version:

```bash
$ composer require react/http-client:^0.5.10
```

See also the [CHANGELOG](CHANGELOG.md) for details about version upgrades.

This project aims to run on any platform and thus does not require any PHP
extensions and supports running on legacy PHP 5.3 through current PHP 7+ and
HHVM.
It's *highly recommended to use PHP 7+* for this project.

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

The test suite also contains a number of functional integration tests that send
test HTTP requests against the online service http://httpbin.org and thus rely
on a stable internet connection.
If you do not want to run these, they can simply be skipped like this:

```bash
$ php vendor/bin/phpunit --exclude-group internet
```

## License

MIT, see [LICENSE file](LICENSE).
