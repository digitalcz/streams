<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use Http\Psr7Test\StreamIntegrationTest;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LimitStream::class)]
class LimitStreamTest extends StreamIntegrationTest
{
    /** @var mixed[] */
    protected $skippedTests = [ // phpcs:ignore
        'testTell' => 'LimitStream positions the source at the window offset',
        'testWrite' => 'LimitStream is not writable',
        'testIsWritable' => 'LimitStream is not writable',
    ];

    /**
     * @inheritDoc
     */
    public function createStream($data): StreamInterface
    {
        return new LimitStream(Stream::from($data), PHP_INT_MAX);
    }

    public function testReadsOnlyTheWindow(): void
    {
        $stream = new LimitStream(Stream::from('abcdefgh'), 3, 2);

        self::assertSame(0, $stream->tell());
        self::assertSame('cde', $stream->read(10));
        self::assertSame('', $stream->read(10));
        self::assertSame(3, $stream->tell());
        self::assertSame(3, $stream->getSize());
        self::assertSame('cde', (string)$stream);
    }

    public function testEofAfterReadingExactlyTheLimit(): void
    {
        $source = Stream::from('abcdef');
        $stream = new LimitStream($source, 3);

        $stream->read(3);

        self::assertFalse($source->eof());
        self::assertTrue($stream->eof());
        self::assertSame('', $stream->getContents());
    }

    public function testLimitBeyondEndOfSource(): void
    {
        $stream = new LimitStream(Stream::from('abcdef'), 100, 4);

        self::assertSame(2, $stream->getSize());
        self::assertSame('ef', $stream->getContents());
        self::assertTrue($stream->eof());

        $stream->seek(10);
        self::assertSame(2, $stream->tell());
        self::assertSame('', $stream->read(1));
    }

    public function testOffsetBeyondEndOfSource(): void
    {
        $stream = new LimitStream(Stream::from('abc'), 10, 5);

        self::assertSame(0, $stream->getSize());
        self::assertSame(0, $stream->tell());
        self::assertSame('', $stream->getContents());
        self::assertTrue($stream->eof());

        $stream->seek(3);
        self::assertSame(0, $stream->tell());
    }

    public function testNeverReadsBeforeTheWindow(): void
    {
        $source = Stream::from('abc');
        $stream = new LimitStream($source, 10, 5);

        $source->seek(0, SEEK_END);
        $source->write('defgh');

        self::assertSame('', $stream->read(10));

        $source->seek(1);
        self::assertSame('', $stream->read(10));
    }

    public function testSeekIsRelativeToTheWindow(): void
    {
        $stream = new LimitStream(Stream::from('abcdefgh'), 4, 2);

        $stream->seek(1);
        self::assertSame('d', $stream->read(1));

        $stream->seek(1, SEEK_CUR);
        self::assertSame('f', $stream->read(1));

        $stream->seek(-1, SEEK_END);
        self::assertSame(3, $stream->tell());
        self::assertSame('f', $stream->read(10));
    }

    public function testSeekPastLimitStopsAtLimit(): void
    {
        $stream = new LimitStream(Stream::from('abcdefgh'), 4, 2);

        $stream->seek(10);

        self::assertSame(4, $stream->tell());
        self::assertSame('', $stream->read(1));
    }

    public function testSeekBeforeWindowThrows(): void
    {
        $stream = new LimitStream(Stream::from('abcdefgh'), 4, 2);

        $this->expectException(StreamException::class);
        $stream->seek(-1);
    }

    public function testSeekEndWithUnknownSourceSizeThrows(): void
    {
        $source = new class (Stream::from('abcdef')) implements StreamInterface {
            use StreamDecoratorTrait;

            public function getSize(): ?int
            {
                return null;
            }
        };
        $stream = new LimitStream($source, 3);

        self::assertNull($stream->getSize());
        $this->expectException(StreamException::class);
        $stream->seek(0, SEEK_END);
    }

    public function testStalledSourceDoesNotLoopForever(): void
    {
        $stalled = new class (Stream::from('')) implements StreamInterface {
            use StreamDecoratorTrait;

            public function eof(): bool
            {
                return false;
            }
        };
        $stream = new LimitStream($stalled, 10);

        self::assertSame('', $stream->getContents());
    }

    public function testToStringReturnsEmptyStringOnError(): void
    {
        $failing = new class (Stream::from('abc')) implements StreamInterface {
            use StreamDecoratorTrait;

            public function read(int $length): string
            {
                throw new StreamException('Read failed');
            }
        };
        $stream = new LimitStream($failing, 2);

        self::assertSame('', (string)$stream);
    }

    public function testInvalidWhenceIsRejected(): void
    {
        $stream = new LimitStream(Stream::from('abc'), 2);

        $this->expectException(InvalidArgumentException::class);
        $stream->seek(0, 42);
    }

    public function testNegativeLimitIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LimitStream(Stream::from('abc'), -1);
    }

    public function testLimitStreamIsNotWritable(): void
    {
        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('This stream is not writable');

        $stream = new LimitStream(Stream::from('test'), 2);
        $stream->write('foo');
    }

    public function testCopyIsNotWritable(): void
    {
        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('This stream is not writable');

        $stream = new LimitStream(Stream::from('test'), 2);
        $stream->copy(Stream::from('bar'));
    }
}
