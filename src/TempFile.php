<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

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

    protected StreamInterface $stream;
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
            throw new StreamException('Unable to create TempFile');
        }

        $this->path = $path;
        $this->stream = $stream;
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
