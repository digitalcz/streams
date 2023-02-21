<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;

final class StreamUtils
{
    /**
     * @param PsrStreamInterface|resource|string $from
     */
    public static function create(mixed $from): StreamInterface
    {
        if ($from instanceof PsrStreamInterface) {
            $from = (string)$from;
        }

        if (is_string($from)) {
            $stream = Stream::temp('rb+');
            $stream->write($from);
            $stream->rewind();

            return $stream;
        }

        if (is_resource($from)) {
            return new Stream($from);
        }

        throw new InvalidArgumentException('Cannot create stream from ' . get_debug_type($from));
    }

    public static function copy(StreamInterface $source, StreamInterface $target): int
    {
        if (!$source->isReadable()) {
            throw new StreamException('Source stream is not readable');
        }

        if (!$target->isWritable()) {
            throw new StreamException('Target stream is not writable');
        }

        $seekable = $source->isSeekable();

        if ($seekable) {
            $sourcePos = $source->tell();
            $source->rewind(); // rewind source to beginning
        }

        $bytes = false;

        while (!$source->eof()) {
            $bytes = $target->write($source->read(1024 ^ 2));

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
}
