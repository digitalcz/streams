<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use PHPUnit\Framework\TestCase;

/**
 * @covers \DigitalCz\Streams\File
 */
class FileTest extends TestCase
{
    public function testOpenNonExistentFile(): void
    {
        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('Failed to open file');
        new File('foo', 'r');
    }

    public function testCreateAndDelete(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'temp');
        self::assertNotFalse($path);
        $file = new File($path);
        self::assertFileExists($file->getPath());
        $file->delete();
        self::assertFileDoesNotExist($file->getPath());
    }

    public function testTemp(): void
    {
        $file = File::temp();

        self::assertStringStartsWith(sys_get_temp_dir(), $file->getPath());
        self::assertSame('plainfile', $file->getMetadata('wrapper_type'));
        self::assertSame('STDIO', $file->getMetadata('stream_type'));
    }
}
