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
class TempFile extends Stream implements FileInterface
{
    private string $path;

    public function __construct()
    {
        $resource = tmpfile();

        if (!is_resource($resource)) {
            throw new StreamException('Unable to create tmpfile');
        }

        parent::__construct($resource);

        $path = $this->getMetadata('uri');

        if (!is_string($path)) {
            throw new StreamException('Unable to create TempFile');
        }

        $this->path = $path;
    }

    public static function fromStream(StreamInterface $stream): self
    {
        $self = new self();
        $self->copy($stream);
        $self->rewind();

        return $self;
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
