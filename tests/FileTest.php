<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

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

    public function testFromStream(): void
    {
        $stream = Stream::from('test');
        $file = File::from($stream);
        self::assertSame('test', $file->getContents());
    }

    public function testFromString(): void
    {
        $file = File::from('test');
        self::assertSame('test', $file->getContents());
    }

    public function testFromFilename(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'temp');
        self::assertNotFalse($path);
        file_put_contents($path, 'test');

        $file = File::from($path);
        self::assertSame('test', $file->getContents());
    }

    public function testFromResource(): void
    {
        $resource = fopen('php://temp', 'wb+');
        self::assertNotFalse($resource);
        fwrite($resource, 'test');
        fseek($resource, 0);

        $file = File::from($resource);
        self::assertSame('test', $file->getContents());
    }

    public function testFromInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create DigitalCz\Streams\File from stdClass');

        $object = new stdClass();
        File::from($object); // @phpstan-ignore-line
    }
}
