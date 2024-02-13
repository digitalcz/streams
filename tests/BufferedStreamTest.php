<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use Http\Psr7Test\StreamIntegrationTest;

/**
 * @covers \DigitalCz\Streams\BufferedStream
 */
class BufferedStreamTest extends StreamIntegrationTest
{
    /** @var mixed[] */
    protected $skippedTests = [ // phpcs:ignore
        'testRewindNotSeekable' => 'BufferedStream makes stream seekable',
        'testIsNotSeekable' => 'BufferedStream makes stream seekable',
        'testWrite' => 'BufferedStream is not writable',
        'testIsWritable' => 'BufferedStream is not writable',
        'testDetach' => 'BufferedStream returns buffer handle on detach',
    ];

    public function testUseOriginalIfAvailable(): void
    {
        $stream = Stream::from('test');
        $buffered = new BufferedStream($stream);
        self::assertSame(4, $buffered->getSize());
    }

    public function testReadCachedByte(): void
    {
        $stream = Stream::from('testing');
        $buffered = new BufferedStream($stream);

        $buffered->seek(5);
        self::assertSame('n', $buffered->read(1));
        $buffered->seek(0);
        self::assertSame('t', $buffered->read(1));
    }

    public function testCanSeekNearEndWithSeekEnd(): void
    {
        $stream = Stream::from(implode('', range('a', 'z')));
        $buffered = new BufferedStream($stream);
        $buffered->seek(-1, SEEK_END);
        self::assertSame(25, $buffered->tell());
        self::assertSame('z', $buffered->read(1));
        self::assertSame(26, $buffered->getSize());
    }

    public function testCanSeekToEndWithSeekEnd(): void
    {
        $stream = Stream::from(implode('', range('a', 'z')));
        $buffered = new BufferedStream($stream);
        $buffered->seek(0, SEEK_END);
        self::assertSame(26, $stream->tell());
        self::assertSame('', $buffered->read(1));
        self::assertSame(26, $buffered->getSize());
    }

    public function testTell(): void
    {
        $resource = fopen('php://memory', 'wb');
        self::assertNotFalse($resource);
        fwrite($resource, 'abcdef');
        $stream = $this->createStream($resource);

        self::assertSame(0, $stream->tell());
        $stream->seek(3);
        self::assertSame(3, $stream->tell());
        $stream->seek(6);
        self::assertSame(6, $stream->tell());
    }

    /**
     * @inheritDoc
     */
    public function createStream($data): StreamInterface
    {
        $source = Stream::from($data);

        if ($source->isSeekable()) {
            $source->rewind();
        }

        return new BufferedStream($source);
    }

    public function testBufferedStreamIsNotWritable(): void
    {
        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('This stream is not writable');

        $stream = Stream::from('test');
        $buffered = new BufferedStream($stream);
        $buffered->write('foo');
    }

    public function testCopyIsNotWritable(): void
    {
        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('This stream is not writable');

        $stream = Stream::from('foo');
        $buffered = new BufferedStream($stream);
        $buffered->copy(Stream::from('bar'));
    }

    public function testDetach(): void
    {
        $alphabet = implode('', range('a', 'z'));

        $stream = Stream::from($alphabet);
        $buffered = new BufferedStream($stream);

        // detaching will return new resource with all contents
        $resource = $buffered->detach();

        // original stream will be closed
        self::assertFalse($stream->isReadable());
        self::assertFalse($stream->isWritable());
        self::assertIsResource($resource);

        $controlStream = Stream::from($resource);
        $controlStream->rewind();
        self::assertSame($alphabet, $controlStream->getContents());
    }
}
