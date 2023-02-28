<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;

/**
 * This class leverages behavior of php function tmpfile()
 *
 * @see https://www.php.net/manual/en/function.tmpfile.php
 *
 * When the underlying stream handle closes, this file will be deleted
 */
final class TempFile implements FileInterface
{
    use StreamDecoratorTrait;

    private string $path;

    public function __construct()
    {
        $resource = tmpfile();

        if (!is_resource($resource)) {
            throw new StreamException('Unable to create tmpfile');
        }

        $stream = new Stream($resource, 0);
        $path = $stream->getMetadata('uri');

        if (!is_string($path)) {
            throw new StreamException(sprintf('Unable to create %s', self::class));
        }

        $this->path = $path;
        $this->stream = $stream;
    }

    /**
     * @param PsrStreamInterface|resource|string $from
     */
    public static function from(mixed $from): self
    {
        if ($from instanceof PsrStreamInterface) {
            $file = new self();
            $file->copy($from);
            $file->rewind();

            return $file;
        }

        if (is_string($from)) {
            $file = new self();
            $file->write($from);
            $file->rewind();

            return $file;
        }

        if (is_resource($from)) {
            $file = new self();
            $file->copy(new Stream($from));
            $file->rewind();

            return $file;
        }

        throw new InvalidArgumentException(sprintf('Cannot create %s from %s.', self::class, get_debug_type($from)));
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function delete(): void
    {
        $this->close();
    }
}
