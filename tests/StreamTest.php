<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Throwable;

class StreamTest extends TestCase
{
    public function testConstructorThrowsExceptionOnInvalidArgument(): void
    {
        $this->expectException(StreamException::class);
        new Stream(true); // @phpstan-ignore-line
    }

    public function testConstructorInitializesProperties(): void
    {
        $handle = $this->createTempResource('r+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);
        self::assertTrue($stream->isReadable());
        self::assertTrue($stream->isWritable());
        self::assertTrue($stream->isSeekable());
        self::assertSame('php://temp', $stream->getMetadata('uri'));
        self::assertIsArray($stream->getMetadata());
        self::assertSame(4, $stream->getSize());
        self::assertFalse($stream->eof());
        $stream->close();
    }

    public function testConstructorInitializesPropertiesWithRbPlus(): void
    {
        $handle = $this->createTempResource('rb+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);
        self::assertTrue($stream->isReadable());
        self::assertTrue($stream->isWritable());
        self::assertTrue($stream->isSeekable());
        self::assertSame('php://temp', $stream->getMetadata('uri'));
        self::assertIsArray($stream->getMetadata());
        self::assertSame(4, $stream->getSize());
        self::assertFalse($stream->eof());
        $stream->close();
    }

    public function testStreamDoesNotCloseAutomaticallyAfterDestroy(): void
    {
        $handle = $this->createTempResource('r');
        $stream = new Stream($handle);
        unset($stream);
        self::assertIsResource($handle); // @phpstan-ignore-line
    }

    public function testConvertsToString(): void
    {
        $handle = $this->createTempResource('w+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);
        self::assertSame('data', (string)$stream);
        self::assertSame('data', (string)$stream);
        $stream->close();
    }

    public function testConvertsToStringNonSeekableStream(): void
    {
        $handle = popen('echo foo', 'r');
        $stream = new Stream($handle); // @phpstan-ignore-line
        self::assertFalse($stream->isSeekable());
        self::assertSame('foo', trim((string)$stream));
    }

    public function testConvertsToStringNonSeekablePartiallyReadStream(): void
    {
        $handle = popen('echo bar', 'r');
        $stream = new Stream($handle); // @phpstan-ignore-line
        $firstLetter = $stream->read(1);
        self::assertFalse($stream->isSeekable());
        self::assertSame('b', $firstLetter);
        self::assertSame('ar', trim((string)$stream));
    }

    public function testGetsContents(): void
    {
        $handle = $this->createTempResource('w+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);
        self::assertSame('data', $stream->getContents());
        $stream->close();
    }

    public function testChecksEof(): void
    {
        $handle = $this->createTempResource('w+');
        fwrite($handle, 'data');
        $stream = new Stream($handle);
        self::assertSame(4, $stream->tell(), 'Stream cursor already at the end');
        self::assertFalse($stream->eof(), 'Stream still not eof');
        self::assertSame('', $stream->read(1), 'Need to read one more byte to reach eof');
        self::assertTrue($stream->eof());
        $stream->close();
    }

    public function testGetSize(): void
    {
        $size = filesize(__FILE__);
        $handle = fopen(__FILE__, 'r');
        $stream = new Stream($handle); // @phpstan-ignore-line
        self::assertSame($size, $stream->getSize());
        // Load from cache
        self::assertSame($size, $stream->getSize());
        $stream->close();
    }

    public function testEnsuresSizeIsConsistent(): void
    {
        $h = $this->createTempResource('w+');
        self::assertSame(3, fwrite($h, 'foo'));
        $stream = new Stream($h);
        self::assertSame(3, $stream->getSize());
        self::assertSame(4, $stream->write('test'));
        self::assertSame(7, $stream->getSize());
        self::assertSame(7, $stream->getSize());
        $stream->close();
    }

    public function testProvidesStreamPosition(): void
    {
        $handle = $this->createTempResource('w+');
        $stream = new Stream($handle);
        self::assertSame(0, $stream->tell());
        $stream->write('foo');
        self::assertSame(3, $stream->tell());
        $stream->seek(1);
        self::assertSame(1, $stream->tell());
        self::assertSame(ftell($handle), $stream->tell());
        $stream->close();
    }

    public function testDetachStreamAndClearProperties(): void
    {
        $handle = $this->createTempResource('r');
        $stream = new Stream($handle);
        self::assertSame($handle, $stream->detach());
        self::assertIsNotClosedResource($handle);
        self::assertNull($stream->detach());

        $this->assertStreamStateAfterClosedOrDetached($stream);

        $stream->close();
    }

    public function testCloseResourceAndClearProperties(): void
    {
        $handle = $this->createTempResource('r');
        $stream = new Stream($handle);
        $stream->close();

        self::assertIsClosedResource($handle);

        $this->assertStreamStateAfterClosedOrDetached($stream);
    }

    public function testStreamReadingWithZeroLength(): void
    {
        $r = $this->createTempResource('r');
        $stream = new Stream($r);

        self::assertSame('', $stream->read(0));

        $stream->close();
    }

    public function testStreamReadingWithNegativeLength(): void
    {
        $r = $this->createTempResource('r');
        $stream = new Stream($r);
        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('Length parameter cannot be negative');

        try {
            $stream->read(-1);
        } catch (Throwable $e) {
            $stream->close();

            throw $e;
        }

        $stream->close();
    }

    #[RequiresPhpExtension('zlib')]
    #[DataProvider('gzipModeProvider')]
    public function testGzipStreamModes(string $mode, bool $readable, bool $writable): void
    {
        $r = gzopen('php://temp', $mode);
        $stream = new Stream($r); // @phpstan-ignore-line

        self::assertSame($readable, $stream->isReadable());
        self::assertSame($writable, $stream->isWritable());

        $stream->close();
    }

    /**
     * @return iterable<array{mode: string, readable: bool, writable: bool}>
     */
    public static function gzipModeProvider(): iterable
    {
        return [
            ['mode' => 'rb9', 'readable' => true, 'writable' => false],
            ['mode' => 'wb2', 'readable' => false, 'writable' => true],
        ];
    }

    #[DataProvider('readableModeProvider')]
    public function testReadableStream(string $mode): void
    {
        $r = $this->createTempResource($mode);
        $stream = new Stream($r);

        self::assertTrue($stream->isReadable());

        $stream->close();
    }

    /**
     * @return iterable<array<string>>
     */
    public static function readableModeProvider(): iterable
    {
        return [
            ['r'],
            ['w+'],
            ['r+'],
            ['x+'],
            ['c+'],
            ['rb'],
            ['w+b'],
            ['r+b'],
            ['x+b'],
            ['c+b'],
            ['rt'],
            ['w+t'],
            ['r+t'],
            ['x+t'],
            ['c+t'],
            ['a+'],
            ['rb+'],
        ];
    }

    public function testWriteOnlyStreamIsNotReadable(): void
    {
        $r = fopen('php://output', 'w');
        $stream = new Stream($r);  // @phpstan-ignore-line

        self::assertFalse($stream->isReadable());

        $stream->close();
    }

    #[DataProvider('writableModeProvider')]
    public function testWritableStream(string $mode): void
    {
        $r = $this->createTempResource($mode);
        $stream = new Stream($r);

        self::assertTrue($stream->isWritable());

        $stream->close();
    }

    /**
     * @return iterable<array<string>>
     */
    public static function writableModeProvider(): iterable
    {
        return [
            ['w'],
            ['w+'],
            ['rw'],
            ['r+'],
            ['x+'],
            ['c+'],
            ['wb'],
            ['w+b'],
            ['r+b'],
            ['rb+'],
            ['x+b'],
            ['c+b'],
            ['w+t'],
            ['r+t'],
            ['x+t'],
            ['c+t'],
            ['a'],
            ['a+'],
        ];
    }

    public function testReadOnlyStreamIsNotWritable(): void
    {
        $r = fopen('php://input', 'rb');
        $stream = new Stream($r); // @phpstan-ignore-line

        self::assertFalse($stream->isWritable());

        $stream->close();
    }

    public function testCopySourceNotReadable(): void
    {
        $target = new Stream($this->createTempResource('wb+'));
        $source = new Stream(fopen('php://output', 'wb')); // @phpstan-ignore-line

        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('Source stream is not readable');
        $target->copy($source);
    }

    public function testCopyTargetNotWritable(): void
    {
        $target = new Stream($this->createTempResource('rb'));
        $source = new Stream($this->createTempResource('wb+'));

        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('Target stream is not writable');
        $target->copy($source);
    }

    public function testCopy(): void
    {
        $target = new Stream($this->createTempResource('wb+'));
        $source = new Stream($this->createTempResource('wb+'));

        $source->write('test');
        $target->copy($source);
        $target->rewind();
        self::assertEquals('test', $target->getContents());
    }

    public function testCopyPurgeSize(): void
    {
        $target = new Stream($this->createTempResource('wb+'));
        $source = new Stream($this->createTempResource('wb+'));

        $source->write('test');
        $sizeBefore = $target->getSize();
        $target->copy($source);
        $target->rewind();

        self::assertNotEquals($sizeBefore, $target->getSize());
    }

    private function assertStreamStateAfterClosedOrDetached(Stream $stream): void
    {
        self::assertFalse($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertFalse($stream->isSeekable());
        self::assertNull($stream->getSize());
        self::assertSame([], $stream->getMetadata());
        self::assertNull($stream->getMetadata('foo'));

        $throws = static function (callable $fn): void {
            try {
                $fn();
            } catch (Throwable $e) {
                self::assertStringContainsString('Stream is detached', $e->getMessage());

                return;
            }

            self::fail('Exception should be thrown after the stream is detached.');
        };

        $throws(static function () use ($stream): void {
            $stream->read(10);
        });
        $throws(static function () use ($stream): void {
            $stream->write('bar');
        });
        $throws(static function () use ($stream): void {
            $stream->seek(10);
        });
        $throws(static function () use ($stream): void {
            $stream->tell();
        });
        $throws(static function () use ($stream): void {
            $stream->eof();
        });
        $throws(static function () use ($stream): void {
            $stream->getContents();
        });
        $throws(static function () use ($stream): void {
            $stream->__toString();
        });
    }

    /**
     * @return resource
     */
    private function createTempResource(string $mode)
    {
        $handle = fopen('php://temp', $mode);

        if ($handle === false) {
            throw new LogicException();
        }

        return $handle;
    }
}
