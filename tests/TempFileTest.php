<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use PHPUnit\Framework\TestCase;

class TempFileTest extends TestCase
{
    public function testCreateAndDelete(): void
    {
        $file = new TempFile();
        self::assertFileExists($file->getPath());
        $file->delete();
        self::assertFileDoesNotExist($file->getPath());
    }

    public function testFromStream(): void
    {
        $stream = new TempStream();
        $stream->write('pokus');
        $file = TempFile::fromStream($stream);

        self::assertEquals(0, $file->tell());
        self::assertEquals('pokus', $file->getContents());
    }
}
