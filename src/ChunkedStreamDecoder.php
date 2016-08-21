<?php

namespace React\HttpClient;

use Evenement\EventEmitterTrait;
use Exception;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;

class ChunkedStreamDecoder implements ReadableStreamInterface
{
    const CRLF = "\r\n";

    use EventEmitterTrait;

    /**
     * @var string
     */
    protected $buffer = '';

    /**
     * @var int
     */
    protected $remainingLength = 0;

    /**
     * @var bool
     */
    protected $nextChunkIsLength = true;

    /**
     * @var ReadableStreamInterface
     */
    protected $stream;

    /**
     * @var bool
     */
    protected $closed = false;

    /**
     * @param ReadableStreamInterface $stream
     */
    public function __construct(ReadableStreamInterface $stream)
    {
        $this->stream = $stream;
        $this->stream->on('data', array($this, 'handleData'));
        $this->stream->on('end',  array($this, 'handleEnd'));
        Util::forwardEvents($this->stream, $this, [
            'error',
        ]);
    }

    /** @internal */
    public function handleData($data)
    {
        $this->buffer .= $data;

        do {
            $bufferLength = strlen($this->buffer);
            $continue = $this->iterateBuffer();
            $iteratedBufferLength = strlen($this->buffer);
        } while (
            $continue &&
            $bufferLength !== $iteratedBufferLength &&
            $iteratedBufferLength > 0 &&
            strpos($this->buffer, static::CRLF) !== false
        );

        if ($this->buffer === false) {
            $this->buffer = '';
        }
    }

    protected function iterateBuffer()
    {
        if (strlen($this->buffer) <= 1) {
            return false;
        }

        if ($this->nextChunkIsLength) {
            $crlfPosition = strpos($this->buffer, static::CRLF);
            if ($crlfPosition === false && strlen($this->buffer) > 1024) {
                $this->emit('error', [
                    new Exception('Chunk length header longer then 1024 bytes'),
                ]);
                $this->close();
                return false;
            }
            if ($crlfPosition === false) {
                return false; // Chunk header hasn't completely come in yet
            }
            $this->nextChunkIsLength = false;
            $lengthChunk = substr($this->buffer, 0, $crlfPosition);
            if (strpos($lengthChunk, ';') !== false) {
                list($lengthChunk) = explode(';', $lengthChunk, 2);
            }
            if (dechex(hexdec($lengthChunk)) !== $lengthChunk) {
                $this->emit('error', [
                    new Exception('Unable to validate "' . $lengthChunk . '" as chunk length header'),
                ]);
                $this->close();
                return false;
            }
            $this->remainingLength = hexdec($lengthChunk);
            $this->buffer = substr($this->buffer, $crlfPosition + 2);
            return true;
        }

        if ($this->remainingLength > 0) {
            $chunkLength = $this->getChunkLength();
            if ($chunkLength === 0) {
                return true;
            }
            $this->emit('data', array(
                substr($this->buffer, 0, $chunkLength),
                $this
            ));
            $this->remainingLength -= $chunkLength;
            $this->buffer = substr($this->buffer, $chunkLength);
            return true;
        }

        $this->nextChunkIsLength = true;
        $this->buffer = substr($this->buffer, 2);

        if (substr($this->buffer, 0, 5) === "0\r\n\r\n") {
            $this->stream->removeListener('data', array($this, 'handleData'));
            $this->stream->removeListener('end',  array($this, 'handleEnd'));
            $this->emit('end');
            return false;
        }
        return true;
    }

    protected function getChunkLength()
    {
        $bufferLength = strlen($this->buffer);

        if ($bufferLength >= $this->remainingLength) {
            return $this->remainingLength;
        }

        return $bufferLength;
    }

    public function pause()
    {
        $this->stream->pause();
    }

    public function resume()
    {
        $this->stream->resume();
    }

    public function isReadable()
    {
        return $this->stream->isReadable();
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        Util::pipe($this, $dest, $options);

        return $dest;
    }

    public function close()
    {
        $this->closed = true;
        return $this->stream->close();
    }

    /** @internal */
    public function handleEnd()
    {
        if ($this->closed) {
            return;
        }

        if ($this->buffer === '') {
            $this->emit('end');
            $this->close();
            return;
        }

        $this->emit(
            'error',
            [
                new Exception('Stream ended with incomplete control code')
            ]
        );
        $this->close();
    }
}
