<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use InvalidArgumentException;

final class CachingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    protected StreamInterface $original;
    protected StreamInterface $stream;
    protected int $skipBytes = 0;

    public function __construct(StreamInterface $stream)
    {
        $this->original = $stream;
        $this->stream = Stream::temp('rb+');
    }

    public function getSize(): ?int
    {
        $originalSize = $this->original->getSize();

        if ($originalSize === null) {
            return null;
        }

        return max($this->stream->getSize(), $originalSize);
    }

    /** @inheritDoc */
    public function seek($offset, $whence = SEEK_SET): void
    {
        $offset = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $offset + $this->tell(),
            SEEK_END => $offset + ($this->original->getSize() ?? $this->stream->copy($this->original)),
            default => throw new InvalidArgumentException('Invalid whence'),
        };

        $diff = $offset - ($this->stream->getSize() ?? 0);

        if ($diff > 0) {
            while ($diff > 0 && !$this->original->eof()) {
                $this->read($diff);
                $diff = $offset - ($this->stream->getSize() ?? 0);
            }
        } else {
            $this->stream->seek($offset);
        }
    }

    /** @inheritDoc */
    public function read($length): string
    {
        $data = $this->stream->read($length);
        $remaining = $length - strlen($data);

        if ($remaining > 0) {
            $originalData = $this->original->read($remaining + $this->skipBytes);

            if ($this->skipBytes > 0) {
                $originalData = substr($originalData, $this->skipBytes);
                $this->skipBytes = max(0, $this->skipBytes - strlen($originalData));
            }

            $data .= $originalData;
            $this->stream->write($originalData);
        }

        return $data;
    }

    /** @inheritDoc */
    public function write($string): int
    {
        $overflow = strlen($string) + $this->tell() - $this->original->tell();

        if ($overflow > 0) {
            $this->skipBytes += $overflow;
        }

        return $this->stream->write($string);
    }

    public function eof(): bool
    {
        return $this->stream->eof() && $this->original->eof();
    }

    public function getContents(): string
    {
        $contents = '';

        while (!$this->eof()) {
            $contents .= $this->read(1024 ^ 2);
        }

        return $contents;
    }

    public function close(): void
    {
        $this->original->close();
        $this->stream->close();
    }
}
