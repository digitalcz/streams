<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use PHPUnit\Framework\TestCase;

class StreamUtilsTest extends TestCase
{
    public function testCopySourceNotReadable(): void
    {
        $target = Stream::temp('rb+');
        $source = new Stream(fopen('php://output', 'wb')); // @phpstan-ignore-line

        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('Source stream is not readable');
        StreamUtils::copy($source, $target);
    }

    public function testCopyTargetNotWritable(): void
    {
        $target = Stream::temp('rb');
        $source = Stream::temp('rb+');

        $this->expectException(StreamException::class);
        $this->expectExceptionMessage('Target stream is not writable');
        StreamUtils::copy($source, $target);
    }

    public function testCopy(): void
    {
        $target = Stream::temp('rb+');
        $source = Stream::temp('rb+');

        $source->write('test');
        StreamUtils::copy($source, $target);
        $target->rewind();
        self::assertEquals('test', $target->getContents());
    }

    public function testCopyPurgeSize(): void
    {
        $target = Stream::temp('rb+');
        $source = Stream::temp('rb+');

        $source->write('test');
        $sizeBefore = $target->getSize();
        StreamUtils::copy($source, $target);
        $target->rewind();

        self::assertNotEquals($sizeBefore, $target->getSize());
    }
}
