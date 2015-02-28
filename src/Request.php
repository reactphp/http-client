<?php

namespace React\HttpClient;

use Evenement\EventEmitterTrait;
use Guzzle\Parser\Message\MessageParser;
use React\Stream\WritableStreamInterface;

/**
 * @event headers-written
 * @event response
 * @event drain
 * @event error
 * @event end
 */
class Request implements WritableStreamInterface
{
    use EventEmitterTrait;

    const STATE_INIT = 0;
    const STATE_WRITING_HEAD = 1;
    const STATE_HEAD_WRITTEN = 2;
    const STATE_END = 3;

    private $connector;
    private $connectorPair;
    private $requestData;
    private $requestOptions;

    private $stream;
    private $buffer;
    private $responseFactory;
    private $response;
    private $state = self::STATE_INIT;

    private $redirectCount;
    private $redirectLocations;

    public function __construct(ConnectorPair $connectorPair, RequestData $requestData, RequestOptions $requestOptions)
    {
        $this->connectorPair = $connectorPair;
        $this->requestData = $requestData;
        $this->requestOptions = $requestOptions;
        $this->connector = $connectorPair->getConnectorForScheme($requestData->getScheme());
        $this->redirectCount = 0;
        $this->redirectLocations = [$requestData->getUrl()];
    }

    public function isWritable()
    {
        return self::STATE_END > $this->state;
    }

    public function writeHead()
    {
        if (self::STATE_WRITING_HEAD <= $this->state) {
            throw new \LogicException('Headers already written');
        }

        $this->state = self::STATE_WRITING_HEAD;
        $requestData = $this->requestData;
        $streamRef = &$this->stream;
        $stateRef = &$this->state;

        return $this
            ->connect()
            ->then(
                function ($stream) use ($requestData, &$streamRef, &$stateRef) {
                    $streamRef = $stream;

                    $stream->on('drain', array($this, 'handleDrain'));
                    $stream->on('data', array($this, 'handleData'));
                    $stream->on('end', array($this, 'handleEnd'));
                    $stream->on('error', array($this, 'handleError'));

                    $requestData->setProtocolVersion('1.0');
                    $headers = (string) $requestData;

                    $stream->write($headers);

                    $stateRef = Request::STATE_HEAD_WRITTEN;

                    $this->emit('headers-written', array($this));
                },
                array($this, 'handleError')
            );
    }

    public function write($data)
    {
        if (!$this->isWritable()) {
            return;
        }

        if (self::STATE_HEAD_WRITTEN <= $this->state) {
            return $this->stream->write($data);
        }

        if (self::STATE_WRITING_HEAD > $this->state) {
            $this->writeHead()
                ->then(function () use ($data) {
                    $this->stream->write($data);
                });
        }

        return false;
    }

    public function end($data = null)
    {
        if (null !== $data && !is_scalar($data)) {
            throw new \InvalidArgumentException('$data must be null or scalar');
        }

        if (null !== $data) {
            $this->write($data);
        } elseif (self::STATE_WRITING_HEAD > $this->state) {
            $this->writeHead();
        }
    }

    public function handleDrain()
    {
        $this->emit('drain', array($this));
    }

    public function handleData($data)
    {
        $this->buffer .= $data;

        if (false !== strpos($this->buffer, "\r\n\r\n")) {
            list($response, $bodyChunk) = $this->parseResponse($this->buffer);

            $this->buffer = null;

            $this->stream->removeListener('drain', array($this, 'handleDrain'));
            $this->stream->removeListener('data', array($this, 'handleData'));
            $this->stream->removeListener('end', array($this, 'handleEnd'));
            $this->stream->removeListener('error', array($this, 'handleError'));

            //Should we respond to any redirects?
            if ($this->isRedirectCode($response->getCode())
                && $this->requestOptions->shouldFollowRedirects()) {

                //Have we reached our maximum redirects?
                if ($this->requestOptions->getMaxRedirects() >= 0
                    && $this->redirectCount >= $this->requestOptions->getMaxRedirects()) {
                    $this->closeError(new \RuntimeException(
                        sprintf("Too many redirects: %u", $this->redirectCount)
                    ));

                    return;
                }

                //Is the location a cyclic redirect?
                $headers = $response->getHeaders();
                if (in_array($headers['Location'], $this->redirectLocations)) {
                    $this->closeError(new \RuntimeException(
                        "Cyclic redirect detected"
                    ));

                    return;
                }

                //Store the next location to prevent cyclic redirects.
                $this->redirectLocations[] = $headers['Location'];
                $this->redirectCount++;

                //Recalibrate to this new location.
                $this->requestData->redirect($response->getCode(), $headers['Location']);
                $this->connector = $this->connectorPair->getConnectorForScheme($this->requestData->getScheme());

                //Clean up and rewind.
                $this->stream->close();
                $this->responseFactory = null;
                $this->state = self::STATE_INIT;

                //Perform the same tricks.
                $this->end();

                return;
            }

            $this->response = $response;

            $response->on('end', function () {
                $this->close();
            });
            $response->on('error', function (\Exception $error) {
                $this->closeError(new \RuntimeException(
                    "An error occured in the response",
                    0,
                    $error
                ));
            });

            $this->emit('response', array($response, $this));

            $response->emit('data', array($bodyChunk));
        }
    }

    public function handleEnd()
    {
        $this->closeError(new \RuntimeException(
            "Connection closed before receiving response"
        ));
    }

    public function handleError($error)
    {
        $this->closeError(new \RuntimeException(
            "An error occurred in the underlying stream",
            0,
            $error
        ));
    }

    public function closeError(\Exception $error)
    {
        if (self::STATE_END <= $this->state) {
            return;
        }
        $this->emit('error', array($error, $this));
        $this->close($error);
    }

    public function close(\Exception $error = null)
    {
        if (self::STATE_END <= $this->state) {
            return;
        }

        $this->state = self::STATE_END;

        if ($this->stream) {
            $this->stream->close();
        }

        $this->emit('end', array($error, $this->response, $this));
    }

    protected function parseResponse($data)
    {
        $parser = new MessageParser();
        $parsed = $parser->parseResponse($data);

        $factory = $this->getResponseFactory();

        $response = $factory(
            $parsed['protocol'],
            $parsed['version'],
            $parsed['code'],
            $parsed['reason_phrase'],
            $parsed['headers']
        );

        return array($response, $parsed['body']);
    }

    protected function connect()
    {
        $host = $this->requestData->getHost();
        $port = $this->requestData->getPort();

        return $this->connector
            ->create($host, $port);
    }

    protected function isRedirectCode($code)
    {
        //Note: 303, 307, 308 status is not supported in HTTP/1.0.
        return in_array($code, [301, 302]);
    }

    public function setResponseFactory($factory)
    {
        $this->responseFactory = $factory;
    }

    public function getResponseFactory()
    {
        if (null === $factory = $this->responseFactory) {
            $stream = $this->stream;

            $factory = function ($protocol, $version, $code, $reasonPhrase, $headers) use ($stream) {
                return new Response(
                    $stream,
                    $protocol,
                    $version,
                    $code,
                    $reasonPhrase,
                    $headers
                );
            };

            $this->responseFactory = $factory;
        }

        return $factory;
    }
}
