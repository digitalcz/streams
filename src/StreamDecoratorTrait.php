<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use Psr\Http\Message\StreamInterface as PsrStreamInterface;

trait StreamDecoratorTrait
{
    protected StreamInterface $stream;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    public function getContents(): string
    {
        return $this->stream->getContents();
    }

    public function close(): void
    {
        $this->stream->close();
    }

    /** @inheritDoc */
    public function getMetadata($key = null)
    {
        return $this->stream->getMetadata($key);
    }

    /** @inheritDoc */
    public function detach()
    {
        return $this->stream->detach();
    }

    public function getSize(): ?int
    {
        return $this->stream->getSize();
    }

    public function eof(): bool
    {
        return $this->stream->eof();
    }

    public function tell(): int
    {
        return $this->stream->tell();
    }

    public function isReadable(): bool
    {
        return $this->stream->isReadable();
    }

    public function isWritable(): bool
    {
        return $this->stream->isWritable();
    }

    public function isSeekable(): bool
    {
        return $this->stream->isSeekable();
    }

    public function rewind(): void
    {
        $this->stream->rewind();
    }

    /** @inheritDoc */
    public function seek($offset, $whence = SEEK_SET): void
    {
        $this->stream->seek($offset, $whence);
    }

    /** @inheritDoc */
    public function read($length): string
    {
        return $this->stream->read($length);
    }

    /** @inheritDoc */
    public function write($string): int
    {
        return $this->stream->write($string);
    }

    public function copy(PsrStreamInterface $source): int
    {
        return $this->stream->copy($source);
    }

    public function __toString(): string
    {
        return (string)$this->stream;
    }
}
