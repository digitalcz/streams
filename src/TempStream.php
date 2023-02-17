<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

class TempStream extends Stream
{
    public function __construct(string $mode = 'rb+')
    {
        $handle = fopen('php://temp', $mode);

        if (!is_resource($handle)) {
            throw new StreamException('Unable to create TempStream');
        }

        parent::__construct($handle);
    }

    public static function fromStream(StreamInterface $stream): self
    {
        $self = new self();
        $self->copy($stream);
        $self->rewind();

        return $self;
    }
}
