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
}
