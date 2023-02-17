<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

class Stream implements StreamInterface
{
    /**
     * @see http://php.net/manual/function.fopen.php
     * @see http://php.net/manual/en/function.gzopen.php
     */
    private const READABLE_MODES = '/r|a\+|ab\+|w\+|wb\+|x\+|xb\+|c\+|cb\+/';
    private const WRITABLE_MODES = '/a|w|r\+|rb\+|rw|x|c/';

    /** @var resource|null */
    private $handle;

    private ?int $size;

    private bool $seekable;

    private bool $readable;

    private bool $writable;

    /**
     * @param resource $stream
     */
    public function __construct($stream, ?int $size = null)
    {
        if (!is_resource($stream)) {
            throw new StreamException('Not a resource');
        }

        $this->size = $size;
        $this->handle = $stream;
        $meta = stream_get_meta_data($this->handle);
        $this->seekable = $meta['seekable'] ?? false; // @phpstan-ignore-line
        $this->readable = (bool)preg_match(self::READABLE_MODES, $meta['mode']);
        $this->writable = (bool)preg_match(self::WRITABLE_MODES, $meta['mode']);
    }

    /**
     * @param string|null $key
     * @return array<string, mixed>|mixed|null
     */
    public function getMetadata($key = null)
    {
        if (!isset($this->handle)) {
            return $key !== null ? null : [];
        }

        $meta = stream_get_meta_data($this->handle);

        return $key !== null ? ($meta[$key] ?? null) : $meta;
    }

    public function close(): void
    {
        if (isset($this->handle)) {
            if (is_resource($this->handle)) {
                fclose($this->handle);
            }

            $this->detach();
        }
    }

    /**
     * @return resource|null
     */
    public function detach()
    {
        if (!isset($this->handle)) {
            return null;
        }

        $result = $this->handle;
        unset($this->handle);
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

    /**
     * @return resource
     */
    public function getHandle()
    {
        if (!isset($this->handle)) {
            throw new StreamException('Stream is detached');
        }

        return $this->handle;
    }

    public function getContents(): string
    {
        if ($this->isSeekable()) {
            $this->rewind();
        }

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

        if (!isset($this->handle)) {
            return null;
        }

        $stats = fstat($this->handle);

        if (is_array($stats) && isset($stats['size'])) { // @phpstan-ignore-line
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

    public function copy(StreamInterface $source): int
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

        // We can't know the size after writing anything
        $this->size = null;
        $bytes = stream_copy_to_stream($source->getHandle(), $this->getHandle());

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
        return $this->getContents();
    }
}
