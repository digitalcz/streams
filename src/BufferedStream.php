<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use Throwable;

final class BufferedStream implements StreamInterface
{
    /** @var StreamInterface The original source stream */
    protected StreamInterface $source;

    /** @var StreamInterface The buffer stream */
    protected StreamInterface $buffer;

    /** @var int How many bytes were written from original to stream */
    protected int $written = 0;

    public function __construct(StreamInterface $source)
    {
        $this->source = $source;
        $this->buffer = Stream::temp('w+b');
    }

    public function getSize(): ?int
    {
        if ($this->source->getSize() === null && $this->source->eof()) {
            return $this->written;
        }

        return $this->source->getSize();
    }

    /** @inheritDoc */
    public function seek($offset, $whence = SEEK_SET): void
    {
        $offset = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $offset + $this->tell(),
            SEEK_END => $offset + ($this->getSize() ?? 0),
            default => throw new InvalidArgumentException('Invalid whence'),
        };

        $diff = $offset - $this->written;

        if ($diff > 0) {
            $this->read($diff);
        } else {
            $this->buffer->seek($offset);
        }
    }

    /** @inheritDoc */
    public function read($length): string
    {
        $data = '';

        if ($this->buffer->tell() !== $this->written) {
            $data = $this->buffer->read($length);
        }

        $bytesRead = strlen($data);

        if ($bytesRead < $length) {
            $sourceData = $this->source->read($length - $bytesRead);
            $this->written += $this->buffer->write($sourceData);
            $data .= $sourceData;
        }

        return $data;
    }

    /** @inheritDoc */
    public function write($string): int
    {
        throw new StreamException('This stream is not writable');
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function eof(): bool
    {
        return $this->source->eof() && $this->buffer->tell() === $this->written;
    }

    public function getContents(): string
    {
        $data = '';

        while (!$this->eof()) {
            $data .= $this->read(1024 ^ 2);
        }

        return $data;
    }

    public function close(): void
    {
        $this->source->close();
        $this->buffer->close();
    }

    /**
     * @return resource|null
     */
    public function detach()
    {
        // read all data from source
        $this->getContents();

        $this->source->close();

        return $this->buffer->detach();
    }

    /** @inheritDoc */
    public function getMetadata($key = null)
    {
        return $this->buffer->getMetadata($key);
    }

    public function isSeekable(): bool
    {
        return $this->buffer->isSeekable();
    }

    public function isReadable(): bool
    {
        return $this->buffer->isReadable();
    }

    public function tell(): int
    {
        return $this->buffer->tell();
    }

    public function rewind(): void
    {
        $this->buffer->rewind();
    }

    public function copy(PsrStreamInterface $source): int
    {
        throw new StreamException('This stream is not writable');
    }

    public function __toString(): string
    {
        try {
            $this->rewind();

            return $this->getContents();
        } catch (Throwable) {
            return '';
        }
    }
}
