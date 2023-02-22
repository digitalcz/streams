<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use PHPUnit\Framework\TestCase;

class CachingStreamTest extends TestCase
{
    public function testUseOriginalIfAvailable(): void
    {
        $stream = Stream::from('test');
        $cached = new CachingStream($stream);
        self::assertSame(4, $cached->getSize());
    }

    public function testReadCachedByte(): void
    {
        $stream = Stream::from('testing');
        $cached = new CachingStream($stream);

        $cached->seek(5);
        self::assertSame('n', $cached->read(1));
        $cached->seek(0);
        self::assertSame('t', $cached->read(1));
    }

    public function testCanSeekNearEndWithSeekEnd(): void
    {
        $stream = Stream::from(implode('', range('a', 'z')));
        $cached = new CachingStream($stream);
        $cached->seek(-1, SEEK_END);
        self::assertSame(25, $stream->tell());
        self::assertSame('z', $cached->read(1));
        self::assertSame(26, $cached->getSize());
    }

    public function testCanSeekToEndWithSeekEnd(): void
    {
        $stream = Stream::from(implode('', range('a', 'z')));
        $cached = new CachingStream($stream);
        $cached->seek(0, SEEK_END);
        self::assertSame(26, $stream->tell());
        self::assertSame('', $cached->read(1));
        self::assertSame(26, $cached->getSize());
    }
}
