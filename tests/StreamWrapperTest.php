<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DigitalCz\Streams\StreamWrapper
 */
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
