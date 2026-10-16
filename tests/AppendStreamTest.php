<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use Http\Psr7Test\StreamIntegrationTest;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AppendStream::class)]
class AppendStreamTest extends StreamIntegrationTest
{
    /** @var mixed[] */
    protected $skippedTests = [ // phpcs:ignore
        'testTell' => 'AppendStream tracks its own position starting at 0',
        'testDetach' => 'AppendStream has no single underlying resource',
        'testWrite' => 'AppendStream is not writable',
        'testIsWritable' => 'AppendStream is not writable',
    ];

    /**
     * @inheritDoc
     */
    public function createStream($data): StreamInterface
    {
        return new AppendStream(Stream::from($data));
    }

    public function testReadsPartsInOrder(): void
    {
        $stream = new AppendStream(Stream::from('abc'), Stream::from('def'), Stream::from('gh'));

        self::assertSame('abcd', $stream->read(4));
        self::assertSame('efgh', $stream->read(10));
        self::assertSame(8, $stream->tell());
        self::assertTrue($stream->eof());
        self::assertSame(8, $stream->getSize());
        self::assertSame('abcdefgh', (string)$stream);
    }

    public function testEmptyParts(): void
    {
        $stream = new AppendStream(
            Stream::from(''),
            Stream::from('ab'),
            Stream::from(''),
            Stream::from('c'),
            Stream::from(''),
        );

        self::assertSame(3, $stream->getSize());
        self::assertSame('abc', $stream->getContents());
        self::assertTrue($stream->eof());
    }

    public function testNoParts(): void
    {
        $stream = new AppendStream();

        self::assertTrue($stream->eof());
        self::assertSame(0, $stream->getSize());
        self::assertSame('', $stream->read(10));
        self::assertSame('', (string)$stream);
    }

    public function testSeekAcrossPartBoundaries(): void
    {
        $stream = new AppendStream(Stream::from('abc'), Stream::from('def'), Stream::from('gh'));
        $stream->read(7);

        $stream->seek(2);
        self::assertSame(2, $stream->tell());
        self::assertSame('cde', $stream->read(3));

        $stream->seek(1, SEEK_CUR);
        self::assertSame('gh', $stream->read(10));

        $stream->seek(-3, SEEK_END);
        self::assertSame(5, $stream->tell());
        self::assertSame('fgh', $stream->getContents());
    }

    public function testNonSeekablePartMakesStreamNonSeekable(): void
    {
        $nonSeekable = new class (Stream::from('def')) implements StreamInterface {
            use StreamDecoratorTrait;

            public function isSeekable(): bool
            {
                return false;
            }
        };
        $stream = new AppendStream(Stream::from('abc'), $nonSeekable);

        self::assertFalse($stream->isSeekable());
        self::assertSame('abcdef', $stream->getContents());

        $this->expectException(StreamException::class);
        $stream->rewind();
    }

    public function testUnknownPartSizeMakesSizeUnknown(): void
    {
        $unknownSize = new class (Stream::from('def')) implements StreamInterface {
            use StreamDecoratorTrait;

            public function getSize(): ?int
            {
                return null;
            }
        };
        $stream = new AppendStream(Stream::from('abc'), $unknownSize);

        self::assertNull($stream->getSize());
        self::assertSame('abcdef', (string)$stream);
    }

    public function testCloseClosesAllParts(): void
    {
        $first = Stream::from('abc');
        $second = Stream::from('def');
        $stream = new AppendStream($first, $second);

        $stream->close();

        self::assertFalse($first->isReadable());
        self::assertFalse($second->isReadable());
        self::assertTrue($stream->eof());
    }

    public function testAppendStreamIsNotWritable(): void
    {
        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('This stream is not writable');

        $stream = new AppendStream(Stream::from('test'));
        $stream->write('foo');
    }

    public function testCopyIsNotWritable(): void
    {
        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('This stream is not writable');

        $stream = new AppendStream(Stream::from('test'));
        $stream->copy(Stream::from('bar'));
    }
}
