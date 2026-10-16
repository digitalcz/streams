<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use Throwable;

/**
 * Read-only view of a window of another stream, starting at $offset and at most $limit bytes long
 */
final class LimitStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private int $limit;

    private int $offset;

    public function __construct(StreamInterface $stream, int $limit, int $offset = 0)
    {
        if ($limit < 0 || $offset < 0) {
            throw new InvalidArgumentException('Limit and offset cannot be negative');
        }

        $this->stream = $stream;
        $this->limit = $limit;
        $this->offset = $offset;

        if ($stream->tell() !== $offset) {
            $stream->seek($offset);
        }
    }

    public function getSize(): ?int
    {
        $size = $this->stream->getSize();

        if ($size === null) {
            return null;
        }

        return max(0, min($this->limit, $size - $this->offset));
    }

    public function eof(): bool
    {
        return $this->stream->eof() || $this->tell() >= $this->limit;
    }

    public function tell(): int
    {
        return $this->stream->tell() - $this->offset;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $position = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $offset + $this->tell(),
            SEEK_END => $offset + ($this->getSize() ?? throw new StreamException('Stream size is unknown')),
            default => throw new InvalidArgumentException('Invalid whence'),
        };

        if ($position < 0) {
            throw new StreamException("Unable to seek to stream position {$position}");
        }

        $this->stream->seek($this->offset + min($position, $this->limit));
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function read(int $length): string
    {
        $remaining = $this->limit - $this->tell();

        if ($length === 0 || $remaining <= 0) {
            return '';
        }

        return $this->stream->read(min($length, $remaining));
    }

    public function getContents(): string
    {
        $data = '';

        while (!$this->eof()) {
            $chunk = $this->read(1024 * 1024);

            if ($chunk === '') {
                break;
            }

            $data .= $chunk;
        }

        return $data;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new StreamException('This stream is not writable');
    }

    public function copy(PsrStreamInterface $source): int
    {
        throw new StreamException('This stream is not writable');
    }

    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (Throwable) {
            return '';
        }
    }
}
