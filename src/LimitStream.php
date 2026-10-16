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
            $this->seekSource(0);
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
        $position = $this->stream->tell() - $this->offset;

        return $this->stream->eof() || $position < 0 || $position >= $this->limit;
    }

    public function tell(): int
    {
        return max(0, $this->stream->tell() - $this->offset);
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

        $this->seekSource($position);
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function read(int $length): string
    {
        $position = $this->stream->tell() - $this->offset;

        // The source may sit before the window (beyond its end, or moved by someone else)
        if ($length === 0 || $position < 0 || $position >= $this->limit) {
            return '';
        }

        return $this->stream->read(min($length, $this->limit - $position));
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

    private function seekSource(int $position): void
    {
        $target = $this->offset + min($position, $this->limit);
        $size = $this->stream->getSize();

        // Seeking past the end fails on some streams (e.g. php://temp before PHP 8.3)
        if ($size !== null) {
            $target = min($target, $size);
        }

        $this->stream->seek($target);
    }
}
