<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use finfo;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;

/**
 * Reads information about the content of any PSR-7 stream without loading it all into memory.
 *
 * Seekable streams are read from the beginning and their position is restored afterwards.
 * Non-seekable streams are read from the current position and the bytes read are consumed,
 * wrap them in BufferedStream if you need to read them again.
 */
final class StreamInfo
{
    private const CHUNK_SIZE = 1024 * 1024;

    /**
     * @param string $algo any algorithm from hash_algos()
     * @return string lowercase hex digest
     */
    public static function hash(PsrStreamInterface $stream, string $algo): string
    {
        if (!in_array($algo, hash_algos(), true)) {
            throw new StreamException("Unknown hashing algorithm {$algo}");
        }

        return self::read($stream, static function (PsrStreamInterface $stream) use ($algo): string {
            $context = hash_init($algo);

            while (!$stream->eof()) {
                $chunk = $stream->read(self::CHUNK_SIZE);

                if ($chunk === '') {
                    break;
                }

                hash_update($context, $chunk);
            }

            return hash_final($context);
        });
    }

    /**
     * Detects the MIME type from the first chunk of the stream, requires ext-fileinfo
     */
    public static function mimeType(PsrStreamInterface $stream): string
    {
        if (!extension_loaded('fileinfo')) {
            throw new StreamException('Detecting MIME type requires the fileinfo extension');
        }

        return self::read($stream, static function (PsrStreamInterface $stream): string {
            $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($stream->read(self::CHUNK_SIZE));

            if (!is_string($mimeType)) {
                throw new StreamException('Unable to detect MIME type');
            }

            return $mimeType;
        });
    }

    /**
     * @param callable(PsrStreamInterface): string $reader
     */
    private static function read(PsrStreamInterface $stream, callable $reader): string
    {
        if (!$stream->isReadable()) {
            throw new StreamException('Stream is not readable');
        }

        if (!$stream->isSeekable()) {
            return $reader($stream);
        }

        $position = $stream->tell();
        $stream->rewind();

        try {
            return $reader($stream);
        } finally {
            $stream->seek($position);
        }
    }
}
