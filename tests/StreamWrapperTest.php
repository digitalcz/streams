<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(StreamWrapper::class)]
class StreamWrapperTest extends TestCase
{
    public function testResource(): void
    {
        $stream = Stream::from('foo');
        $handle = StreamWrapper::from($stream);
        self::assertSame('foo', fread($handle, 3));
        self::assertSame(3, ftell($handle));
        self::assertSame(3, fwrite($handle, 'bar'));
        self::assertSame(0, fseek($handle, 0));
        self::assertSame('foobar', fread($handle, 6));
        self::assertSame('', fread($handle, 1));
        self::assertTrue(feof($handle));

        $stBlksize  = defined('PHP_WINDOWS_VERSION_BUILD') ? -1 : 0;

        self::assertEquals([
            'dev'     => 0,
            'ino'     => 0,
            'mode'    => 33206,
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => 6,
            'atime'   => 0,
            'mtime'   => 0,
            'ctime'   => 0,
            'blksize' => $stBlksize,
            'blocks'  => $stBlksize,
            0         => 0,
            1         => 0,
            2         => 33206,
            3         => 0,
            4         => 0,
            5         => 0,
            6         => 0,
            7         => 6,
            8         => 0,
            9         => 0,
            10        => 0,
            11        => $stBlksize,
            12        => $stBlksize,
        ], fstat($handle));

        self::assertTrue(fclose($handle));
        self::assertSame('foobar', (string) $stream);
    }

    public function testStreamContext(): void
    {
        StreamWrapper::register();
        $stream = Stream::from('foo');

        self::assertSame(
            'foo',
            file_get_contents('digitalcz-streams://stream', false, StreamWrapper::createStreamContext($stream)),
        );
    }

    public function testStreamCast(): void
    {
        $streams = [
            StreamWrapper::from(Stream::from('foo')),
            StreamWrapper::from(Stream::from('bar')),
        ];
        $write = null;
        $except = null;
        self::assertIsInt(stream_select($streams, $write, $except, 0));
    }

    /**
     * @return iterable<string, array{StreamInterface}>
     */
    public static function decoratedStreams(): iterable
    {
        yield 'File' => [File::from('foo')];
        yield 'TempFile' => [TempFile::from('foo')];
        yield 'BufferedStream' => [new BufferedStream(Stream::from('foo'))];
    }

    #[DataProvider('decoratedStreams')]
    public function testStreamCastDoesNotDetachDecoratedStream(StreamInterface $stream): void
    {
        $streams = [StreamWrapper::from(Stream::from('bar')), StreamWrapper::from($stream)];
        $write = null;
        $except = null;
        @stream_select($streams, $write, $except, 0);

        self::assertSame(0, $stream->tell());
        self::assertSame('foo', (string) $stream);
    }

    public function testSeekFailureIsReportedToFseek(): void
    {
        $stream = self::createStub(StreamInterface::class);
        $stream->method('isReadable')->willReturn(true);
        $stream->method('seek')->willThrowException(new StreamException('Stream is not seekable'));

        self::assertSame(-1, fseek(StreamWrapper::from($stream), 1));
    }

    public function testTellFailureIsReportedToFseek(): void
    {
        $stream = self::createStub(StreamInterface::class);
        $stream->method('isReadable')->willReturn(true);
        $stream->method('tell')->willThrowException(new StreamException('Unable to determine stream position'));

        self::assertSame(-1, fseek(StreamWrapper::from($stream), 1));
    }

    public function testReadFailureIsReportedToFread(): void
    {
        $stream = self::createStub(StreamInterface::class);
        $stream->method('isReadable')->willReturn(true);
        $stream->method('read')->willThrowException(new StreamException('Unable to read from stream'));

        self::assertFalse(fread(StreamWrapper::from($stream), 1));
    }

    public function testWriteFailureIsReportedToFwrite(): void
    {
        $stream = self::createStub(StreamInterface::class);
        $stream->method('isWritable')->willReturn(true);
        $stream->method('write')->willThrowException(new StreamException('Unable to write to stream'));

        self::assertFalse(fwrite(StreamWrapper::from($stream), 'foo'));
    }

    public function testValidatesStream(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())
            ->method('isReadable')
            ->willReturn(false);
        $stream->expects(self::once())
            ->method('isWritable')
            ->willReturn(false);

        $this->expectException(InvalidArgumentException::class);
        StreamWrapper::from($stream);
    }

    public function testCanOpenReadonlyStream(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->expects(self::once())
            ->method('isReadable')
            ->willReturn(false);
        $stream->expects(self::once())
            ->method('isWritable')
            ->willReturn(true);
        $r = StreamWrapper::from($stream);
        self::assertIsResource($r);
        fclose($r);
    }

    public function testUrlStat(): void
    {
        StreamWrapper::register();

        $stBlksize  = defined('PHP_WINDOWS_VERSION_BUILD') ? -1 : 0;

        self::assertEquals(
            [
                'dev'     => 0,
                'ino'     => 0,
                'mode'    => 0,
                'nlink'   => 0,
                'uid'     => 0,
                'gid'     => 0,
                'rdev'    => 0,
                'size'    => 0,
                'atime'   => 0,
                'mtime'   => 0,
                'ctime'   => 0,
                'blksize' => $stBlksize,
                'blocks'  => $stBlksize,
                0         => 0,
                1         => 0,
                2         => 0,
                3         => 0,
                4         => 0,
                5         => 0,
                6         => 0,
                7         => 0,
                8         => 0,
                9         => 0,
                10        => 0,
                11        => $stBlksize,
                12        => $stBlksize,
            ],
            stat('digitalcz-streams://stream'),
        );
    }
}
