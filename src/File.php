<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

final class File implements FileInterface
{
    use StreamDecoratorTrait;

    protected StreamInterface $stream;
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
        $path = tempnam(sys_get_temp_dir(), 'temp');

        if (!is_string($path)) {
            throw new StreamException('Failed to create temp name');
        }

        return new self($path);
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
