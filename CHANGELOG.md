# Changelog

## 0.5.10 (2020-01-14)

*   Fix: Avoid unneeded warning when decoding invalid data on PHP 7.4.
    (#150 by @clue)

*   Add `.gitattributes` to exclude dev files from exports.
    (#149 by @reedy)

*   Link to clue/reactphp-buzz for higher-level HTTP client.
    (#139 by @clue)

*   Improve test suite by simplifying test matrix and test setup.
    (#151 by @clue)

## 0.5.9 (2018-04-10)

*   Feature: Support legacy HTTP servers that use only `LF` instead of `CRLF`.
    (#130 by @clue)

*   Improve test suite by applying maximum test timeouts for integration tests.
    (#131 by @clue)

## 0.5.8 (2018-02-09)

*   Support legacy PHP 5.3 through PHP 7.2 and HHVM
    (#126 and #127 by @clue)

*   Improve backwards compatibility with Promise v1 and
    use RingCentral to improve interoperability with react/http.
    (#124 and #125 by @clue)

## 0.5.7 (2018-02-08)

*   Fix: Ignore excessive whitespace in chunk header for `Transfer-Encoding: chunked`
    (#123 by @DangerLifter and @clue)

*   Fix: Ignore invalid incoming `Transfer-Encoding` response header
    (#122 by @clue)

*   Improve documentation for `Client` (and advanced `Connector`)
    (#111 by @jsor and #121 by @clue)

*   Improve test suite by adding support for PHPUnit 6
    (#112 by @carusogabriel)

## 0.5.6 (2017-09-17)

*   Feature: Update Socket component to support HTTP over Unix domain sockets (UDS)
    (#110 by @clue)

## 0.5.5 (2017-09-10)

*   Fix: Update Socket component to work around sending secure HTTPS requests with PHP < 7.1.4
    (#109 by @clue)

## 0.5.4 (2017-08-25)

*   Feature: Update Socket dependency to support hosts file on all platforms
    (#108 by @clue)

    This means that HTTP requests to hosts such as `localhost` will now work as
    expected across all platforms with no changes required:

    ```php
    $client = new Client($loop);
    $request = $client->request('GET', 'http://localhost/');
    $request->on('response', function (Response $response) {
        // …
    });
    $request->end();
    ```

## 0.5.3 (2017-08-16)

*   Feature: Target evenement 3.0 a long side 2.0
    (#106 by @WyriHaximus)

*   Improve test suite by locking Travis distro so new defaults will not break the build
    (#105 by @clue)

## 0.5.2 (2017-06-27)

*   Feature: Support passing arrays for request header values
    (#100 by @clue)

*   Fix: Fix merging default headers if overwritten with custom case headers
    (#101 by @clue)

## 0.5.1 (2017-06-18)

*   Feature: Emit `error` event if request URL is invalid
    (#99 by @clue)

*   Feature: Support OPTIONS method with asterisk-form (`OPTIONS * HTTP/1.1`)
    (#98 by @clue)

*   Improve documentation for event semantics
    (#97 by @clue)

## 0.5.0 (2017-05-22)

*   Feature / BC break: Replace `Factory` with simple `Client` constructor
    (#85 by @clue)

    The `Client` now accepts a required `LoopInterface` and an optional
    `ConnectorInterface`. It will now create a default `Connector` if none
    has been given.

    ```php
    // old
    $dnsResolverFactory = new React\Dns\Resolver\Factory();
    $dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $loop);
    $factory = new React\HttpClient\Factory();
    $client = $factory->create($loop, $dnsResolver);

    // new
    $client = new React\HttpClient\Client($loop);
    ```

*   Feature: `Request::close()` now cancels pending connection attempt
    (#91 by @clue)

*   Feature / BC break: Replace deprecated SocketClient with new Socket component
    (#74, #84 and #88 by @clue)

*   Feature / BC break: Consistent stream semantics and forward compatibility with upcoming Stream v1.0
    (#90 by @clue)

*   Feature: Forward compatibility with upcoming EventLoop v1.0 and v0.5
    (#89 by @clue)

*   Fix: Catch Guzzle parser exception
    (#82 by @djagya)

## 0.4.17 (2017-03-20)

* Improvement: Add PHPUnit to require-dev #75 @jsor
* Fix: Fix chunk header to be case-insensitive and allow leading zeros for end chunk #77 @mdrost 

## 0.4.16 (2017-03-01)

* Fix: Trim leading zeros from chunk size #73 @maciejmrozinski 

## 0.4.15 (2016-12-02)

* Improvement: Add examples #69 @clue
* Fix: Ensure checking for 0 length chunk, when we should check for it #71 @WyriHaximus 

## 0.4.14 (2016-10-28)

* Fix: Ensure the first bit of body directly after the headers is emitted into the stream #68 @WyriHaximus 

## 0.4.13 (2016-10-13)

* Fix: Ensure Request emits initial Response data as string #66 @mmelvin0 

## 0.4.12 (2016-10-06)

* Fix: Changed $stream from DuplexStreamInterface to ReadableStreamInterface in Response constructor #63 @WyriHaximus

## 0.4.11 (2016-09-15)

* Feature: Chunked encoding @WyriHaximus

## 0.4.10 (2016-03-21)

* Improvement: Update react/socket-client dependency to all supported versions @clue

## 0.4.9 (2016-03-08)

* Improvement: PHP 7 memory leak, related to PHP bug [71737](https://bugs.php.net/bug.php?id=71737) @jmalloc
* Improvement: Clean up all listeners when closing request @weichenlin

## 0.4.8 (2015-10-05)

* Improvement: Avoid hiding exceptions thrown in HttpClient\Request error handlers @arnaud-lb

## 0.4.7 (2015-09-24)

* Improvement: Set protocol version on request creation @WyriHaximus

## 0.4.6 (2015-09-20)

* Improvement: Support explicitly using HTTP/1.1 protocol version @clue

## 0.4.5 (2015-08-31)

* Improvement: Replaced the abandoned guzzle/parser with guzzlehttp/psr7 @WyriHaximus

## 0.4.4 (2015-06-16)

* Improvement: Emit drain event when the request is ready to receive more data by @arnaud-lb

## 0.4.3 (2015-06-15)

* Improvement: Added support for using auth informations from URL by @arnaud-lb

## 0.4.2 (2015-05-14)

* Improvement: Pass Response object on with data emit by @dpovshed

## 0.4.1 (2014-11-23)

* Improvement: Use EventEmitterTrait instead of base class by @cursedcoder
* Improvement: Changed Stream to DuplexStreamInterface in Response::__construct by @mbonneau

## 0.4.0 (2014-02-02)

* BC break: Drop unused `Response::getBody()`
* BC break: Bump minimum PHP version to PHP 5.4, remove 5.3 specific hacks
* BC break: Remove `$loop` argument from `HttpClient`: `Client`, `Request`, `Response`
* BC break: Update to React/Promise 2.0
* Dependency: Autoloading and filesystem structure now PSR-4 instead of PSR-0
* Bump React dependencies to v0.4

## 0.3.2 (2016-03-25)

* Improvement: Broader guzzle/parser version req @cboden 
* Improvement: Improve forwards compatibility with all supported versions @clue 

## 0.3.1 (2013-04-21)

* Bug fix: Correct requirement for socket-client

## 0.3.0 (2013-04-14)

* BC break: Socket connection handling moved to new SocketClient component
* Bump React dependencies to v0.3

## 0.2.6 (2012-12-26)

* Version bump

## 0.2.5 (2012-11-26)

* Feature: Use a promise-based API internally
* Bug fix: Use DNS resolver correctly

## 0.2.3 (2012-11-14)

* Version bump

## 0.2.2 (2012-10-28)

* Feature: HTTP client (@arnaud-lb)
