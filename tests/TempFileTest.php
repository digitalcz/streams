<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \DigitalCz\Streams\TempFile
 */
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
        $stream = Stream::from('test');
        $tempFile = TempFile::from($stream);
        self::assertSame('test', $tempFile->getContents());
    }

    public function testFromString(): void
    {
        $tempFile = TempFile::from('test');
        self::assertSame('test', $tempFile->getContents());
    }

    public function testFromResource(): void
    {
        $resource = fopen('php://temp', 'wb+');
        self::assertNotFalse($resource);
        fwrite($resource, 'test');
        fseek($resource, 0);

        $tempFile = TempFile::from($resource);
        self::assertSame('test', $tempFile->getContents());
    }

    public function testFromInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot create DigitalCz\Streams\TempFile from stdClass');

        $object = new stdClass();
        TempFile::from($object); // @phpstan-ignore-line
    }
}
