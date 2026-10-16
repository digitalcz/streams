<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use Throwable;

/**
 * Read-only stream that reads the given streams one after another
 */
final class AppendStream implements StreamInterface
{
    /** @var list<StreamInterface> */
    private array $streams;

    private int $current = 0;

    private int $position = 0;

    public function __construct(StreamInterface ...$streams)
    {
        foreach ($streams as $stream) {
            if (!$stream->isReadable()) {
                throw new InvalidArgumentException('All streams must be readable');
            }
        }

        $this->streams = array_values($streams);
    }

    public function getSize(): ?int
    {
        $size = 0;

        foreach ($this->streams as $stream) {
            $streamSize = $stream->getSize();

            if ($streamSize === null) {
                return null;
            }

            $size += $streamSize;
        }

        return $size;
    }

    public function eof(): bool
    {
        $last = count($this->streams) - 1;

        return $this->current > $last || ($this->current === $last && $this->streams[$last]->eof());
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function isSeekable(): bool
    {
        foreach ($this->streams as $stream) {
            if (!$stream->isSeekable()) {
                return false;
            }
        }

        return true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new StreamException('Stream is not seekable');
        }

        $position = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $offset + $this->tell(),
            SEEK_END => $offset + ($this->getSize() ?? throw new StreamException('Stream size is unknown')),
            default => throw new InvalidArgumentException('Invalid whence'),
        };

        if ($position < 0) {
            throw new StreamException("Unable to seek to stream position {$position}");
        }

        foreach ($this->streams as $stream) {
            $stream->rewind();
        }

        $this->current = 0;
        $this->position = 0;

        while ($this->position < $position) {
            if ($this->read(min(1024 * 1024, $position - $this->position)) === '') {
                break;
            }
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function read(int $length): string
    {
        if ($length < 0) {
            throw new StreamException('Length parameter cannot be negative');
        }

        $data = '';

        while (strlen($data) < $length && $this->current < count($this->streams)) {
            $stream = $this->streams[$this->current];
            $chunk = $stream->read($length - strlen($data));
            $data .= $chunk;

            if ($chunk === '') {
                if (!$stream->eof()) {
                    break;
                }

                $this->current++;
            }
        }

        $this->position += strlen($data);

        return $data;
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

    public function isReadable(): bool
    {
        return true;
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

    public function close(): void
    {
        foreach ($this->streams as $stream) {
            $stream->close();
        }

        $this->detach();
    }

    /**
     * @return resource|null
     */
    public function detach()
    {
        $this->streams = [];
        $this->current = 0;
        $this->position = 0;

        return null;
    }

    /** @inheritDoc */
    public function getMetadata(?string $key = null)
    {
        return $key !== null ? null : [];
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
