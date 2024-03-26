<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;

final class File implements FileInterface
{
    use StreamDecoratorTrait;

    private string $path;

    public function __construct(string $path, string $mode = 'rb+')
    {
        $resource = @fopen($path, $mode);

        if (!is_resource($resource)) {
            throw new StreamException('Failed to open file' . $path);
        }

        $size = @filesize($path);

        if (!is_int($size)) {
            throw new StreamException('Failed to get size of ' . $path);
        }

        $this->path = $path;
        $this->stream = new Stream($resource, $size);
    }

    public static function temp(): self
    {
        $path = tempnam(sys_get_temp_dir(), 'digitalcz-streams-temp');

        if (!is_string($path)) {
            throw new StreamException('Failed to create temp name');
        }

        return new self($path);
    }

    /**
     * @param PsrStreamInterface|resource|string $from
     */
    public static function from(mixed $from): self
    {
        if ($from instanceof PsrStreamInterface) {
            $file = self::temp();
            $file->copy($from);
            $file->rewind();

            return $file;
        }

        if (is_string($from)) {
            if (file_exists($from)) {
                return new self($from);
            }

            $file = self::temp();
            $file->write($from);
            $file->rewind();

            return $file;
        }

        if (is_resource($from)) {
            $file = self::temp();
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

        if (@unlink($this->getPath()) === false) {
            throw new StreamException('Failed to delete file ' . $this->getPath());
        }
    }
}
