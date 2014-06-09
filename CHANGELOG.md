# Changelog

## 0.4.0 (2014-02-02)

* BC break: Drop unused `Response::getBody()`
* BC break: Bump minimum PHP version to PHP 5.4, remove 5.3 specific hacks
* BC break: Remove `$loop` argument from `HttpClient`: `Client`, `Request`, `Response`
* BC break: Update to React/Promise 2.0
* Dependency: Autoloading and filesystem structure now PSR-4 instead of PSR-0
* Bump React dependencies to v0.4

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
