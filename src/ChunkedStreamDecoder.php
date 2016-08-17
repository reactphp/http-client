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
    const MAX_BUFFER_LENGTH = 32768; // 32KB

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
     * @var int
     */
    protected $maxBufferLength;

    /**
     * @var bool
     */
    protected $nextChunkIsLength = true;

    /**
     * @var ReadableStreamInterface
     */
    protected $stream;

    /**
     * @param ReadableStreamInterface $stream
     * @param int $maxBufferLength
     */
    public function __construct(ReadableStreamInterface $stream, $maxBufferLength = self::MAX_BUFFER_LENGTH)
    {
        $this->stream = $stream;
        $this->stream->on('data', array($this, 'handleData'));
        Util::forwardEvents($this->stream, $this, [
            'error',
        ]);
        $this->maxBufferLength = $maxBufferLength;
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
            strpos($this->buffer, static::CRLF) !== false &&
            $iteratedBufferLength <= $this->maxBufferLength
        );

        if ($iteratedBufferLength >= $this->maxBufferLength) {
            $this->emit('error', [
                new Exception('The current buffer is longer then the ' . $this->maxBufferLength / 1024 . 'KB we have set as maximum'),
            ]);
            $this->close();
            return false;
        }
    }

    protected function iterateBuffer()
    {
        if (strlen($this->buffer) <= 1) {
            return false;
        }

        if ($this->nextChunkIsLength) {
            $crlfPosition = strpos($this->buffer, static::CRLF);
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
                    new Exception('Unable to validate "' . $lengthChunk . '" as chunk length header"'),
                ]);
                $this->close();
                return false;
            }
            $this->remainingLength = hexdec($lengthChunk);
            if ($this->remainingLength >= $this->maxBufferLength) {
                $this->emit('error', [
                    new Exception('Expected chunk length is longer then the ' . $this->maxBufferLength / 1024 . 'KB we have set as maximum'),
                ]);
                $this->close();
                return false;
            }
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
        return $this->stream->close();
    }
}
