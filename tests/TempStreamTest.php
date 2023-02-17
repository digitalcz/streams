<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use PHPUnit\Framework\TestCase;

class TempStreamTest extends TestCase
{
    public function testWriteAndRead(): void
    {
        $temp = new TempStream();
        $bytes = random_bytes(10);
        $temp->write($bytes);
        self::assertEquals(10, $temp->getSize());
        $temp->rewind();
        self::assertEquals($bytes, $temp->getContents());
    }

    public function testFromStream(): void
    {
        $source = new TempStream();
        $source->write('pokus');
        $stream = TempStream::fromStream($source);

        self::assertEquals(0, $stream->tell());
        self::assertEquals('pokus', $stream->getContents());
    }
}
