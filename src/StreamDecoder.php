<?php

namespace React\HttpClient;

use Clue\React\Zlib\ZlibFilterStream;
use GuzzleHttp\Psr7\Response as psr7Response;
use React\Stream\DuplexStreamInterface;

class StreamDecoder
{
    public static function detect(DuplexStreamInterface $stream, psr7Response $response)
    {
        if ($response->hasHeader('content-encoding')) {
            $stream = static::contentEncoding($stream, $response->getHeaderLine('content-encoding'));
        }

        return $stream;
    }

    protected static function contentEncoding($stream, $encoding)
    {
        if ($encoding == 'gzip') {
            return $stream->pipe(ZlibFilterStream::createGzipDecompressor());
        }

        if ($encoding == 'deflate') {
            return $stream->pipe(ZlibFilterStream::createDeflateDecompressor());
        }

        return $stream;
    }
}
