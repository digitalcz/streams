<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;

final class Stream implements StreamInterface
{
    /**
     * @see http://php.net/manual/function.fopen.php
     * @see http://php.net/manual/en/function.gzopen.php
     */
    private const READABLE_MODES = '/r|a\+|ab\+|w\+|wb\+|x\+|xb\+|c\+|cb\+/';
    private const WRITABLE_MODES = '/a|w|r\+|rb\+|rw|x|c/';

    /** @var resource|null */
    private $resource;

    private ?int $size;

    private bool $seekable;

    private bool $readable;

    private bool $writable;

    /**
     * @param resource $resource
     */
    public function __construct($resource, ?int $size = null)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('Not a resource');
        }

        $this->size = $size;
        $this->resource = $resource;
        $meta = stream_get_meta_data($this->resource);
        $this->seekable = $meta['seekable'] ?? false; // @phpstan-ignore-line
        $this->readable = (bool)preg_match(self::READABLE_MODES, $meta['mode']);
        $this->writable = (bool)preg_match(self::WRITABLE_MODES, $meta['mode']);
    }

    public static function temp(string $mode): self
    {
        $handle = fopen('php://temp', $mode);

        if (!is_resource($handle)) {
            throw new StreamException('Unable to create TempStream');
        }

        return new self($handle, 0);
    }

    /**
     * @param PsrStreamInterface|resource|string $from
     */
    public static function from(mixed $from): self
    {
        if ($from instanceof PsrStreamInterface) {
            $stream = self::temp('rb+');
            $stream->copy($from);
            $stream->rewind();

            return $stream;
        }

        if (is_string($from)) {
            $stream = self::temp('rb+');
            $stream->write($from);
            $stream->rewind();

            return $stream;
        }

        if (is_resource($from)) {
            return new Stream($from);
        }

        throw new InvalidArgumentException('Cannot create Stream from ' . get_debug_type($from));
    }

    /**
     * @param string|null $key
     * @return array<string, mixed>|mixed|null
     */
    public function getMetadata($key = null)
    {
        if (!isset($this->resource)) {
            return $key !== null ? null : [];
        }

        $meta = stream_get_meta_data($this->resource);

        return $key !== null ? ($meta[$key] ?? null) : $meta;
    }

    public function close(): void
    {
        if (isset($this->resource)) {
            if (is_resource($this->resource)) {
                fclose($this->resource);
            }

            $this->detach();
        }
    }

    /**
     * @return resource|null
     */
    public function detach()
    {
        if (!isset($this->resource)) {
            return null;
        }

        $result = $this->resource;
        unset($this->resource);
        $this->size = null;
        $this->readable = $this->writable = $this->seekable = false;

        return $result;
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * @param int $offset
     * @param int $whence
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        $handle = $this->getHandle();

        if (!$this->isSeekable()) {
            throw new StreamException('Stream is not seekable');
        }

        if (fseek($handle, $offset, $whence) !== 0) {
            throw new StreamException("Unable to seek to stream position {$offset} with whence {$whence}");
        }
    }

    public function getContents(): string
    {
        $contents = stream_get_contents($this->getHandle());

        if (!is_string($contents)) {
            throw new StreamException('Unable to read stream contents');
        }

        return $contents;
    }

    public function getSize(): ?int
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if (!isset($this->resource)) {
            return null;
        }

        $stats = fstat($this->resource);

        if (is_array($stats) && isset($stats['size'])) {
            $this->size = $stats['size'];

            return $this->size;
        }

        return null;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function eof(): bool
    {
        return feof($this->getHandle());
    }

    public function tell(): int
    {
        $result = ftell($this->getHandle());

        if (!is_int($result)) {
            throw new StreamException('Unable to determine stream position');
        }

        return $result;
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * @param int $length
     */
    public function read($length): string
    {
        if ($length < 0) {
            throw new StreamException('Length parameter cannot be negative');
        }

        $handle = $this->getHandle();

        if (!$this->isReadable()) {
            throw new StreamException('Stream is not readable');
        }

        if ($length === 0) {
            return '';
        }

        $string = fread($handle, $length);

        if (!is_string($string)) {
            throw new StreamException('Unable to read from stream');
        }

        return $string;
    }

    /**
     * @param string $string
     */
    public function write($string): int
    {
        $handle = $this->getHandle();

        if (!$this->isWritable()) {
            throw new StreamException('Stream is not writable');
        }

        // We can't know the size after writing anything
        $this->size = null;
        $result = fwrite($handle, $string);

        if (!is_int($result)) {
            throw new StreamException('Unable to write to stream');
        }

        return $result;
    }

    public function copy(PsrStreamInterface $source): int
    {
        if (!$source->isReadable()) {
            throw new StreamException('Source stream is not readable');
        }

        if (!$this->isWritable()) {
            throw new StreamException('Target stream is not writable');
        }

        $seekable = $source->isSeekable();

        if ($seekable) {
            $sourcePos = $source->tell();
            $source->rewind(); // rewind source to beginning
        }

        $bytes = false;

        while (!$source->eof()) {
            $bytes = $this->write($source->read(1024 ^ 2));

            if ($bytes === 0) {
                break;
            }
        }

        if ($seekable) {
            $source->seek($sourcePos); // forward source to previous position
        }

        if (!is_int($bytes)) {
            throw new StreamException('Failed to copy stream');
        }

        return $bytes;
    }

    /**
     * Closes the stream when the destructed
     */
    public function __destruct()
    {
        $this->detach();
    }

    public function __toString(): string
    {
        if ($this->isSeekable()) {
            $this->rewind();
        }

        return $this->getContents();
    }

    /**
     * @return resource
     */
    private function getHandle()
    {
        if (!isset($this->resource)) {
            throw new StreamException('Stream is detached');
        }

        return $this->resource;
    }
}
