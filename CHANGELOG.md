# Changelog

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
