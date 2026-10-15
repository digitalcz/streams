<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use Http\Psr7Test\StreamIntegrationTest;
use PHPUnit\Framework\Attributes\CoversTrait;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;

#[CoversTrait(StreamDecoratorTrait::class)]
class StreamDecoratorTraitTest extends StreamIntegrationTest
{
    /** @inheritDoc */
    public function createStream($data): PsrStreamInterface
    {
        $stream = Stream::from($data);

        return new class ($stream) implements StreamInterface {
            use StreamDecoratorTrait;
        };
    }
}
