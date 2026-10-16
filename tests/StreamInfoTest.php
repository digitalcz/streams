<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(StreamInfo::class)]
class StreamInfoTest extends TestCase
{
    public function testHashMatchesKnownDigest(): void
    {
        self::assertSame(
            'b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9',
            StreamInfo::hash(Stream::from('hello world'), 'sha256'),
        );
    }

    public function testHashOfContentSpanningMultipleChunks(): void
    {
        $content = str_repeat('0123456789', 250_000) . 'x';

        self::assertSame(hash('sha256', $content), StreamInfo::hash(Stream::from($content), 'sha256'));
        self::assertSame(hash('md5', $content), StreamInfo::hash(File::fromString($content), 'md5'));
    }

    public function testHashReadsSeekableStreamFromStartAndRestoresPosition(): void
    {
        $stream = Stream::from('hello world');
        $stream->seek(6);

        self::assertSame(hash('sha256', 'hello world'), StreamInfo::hash($stream, 'sha256'));
        self::assertSame(6, $stream->tell());
        self::assertSame('world', $stream->getContents());
    }

    public function testHashReadsNonSeekableStreamFromCurrentPosition(): void
    {
        $stream = $this->nonSeekable('hello world');
        $stream->read(6);

        self::assertSame(hash('sha256', 'world'), StreamInfo::hash($stream, 'sha256'));
    }

    public function testHashThrowsOnUnknownAlgorithm(): void
    {
        $this->expectException(StreamException::class);
        StreamInfo::hash(Stream::from('hello world'), 'foo');
    }

    public function testHashThrowsOnUnreadableStream(): void
    {
        $this->expectException(StreamException::class);
        StreamInfo::hash($this->unreadable(), 'sha256');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideMimeTypes(): iterable
    {
        yield 'png' => ["\x89PNG\r\n\x1a\n\0\0\0\rIHDR\0\0\0\x01\0\0\0\x01\x08\x06\0\0\0\x1f\x15\xc4\x89", 'image/png'];
        yield 'pdf' => ["%PDF-1.4\n%\xe2\xe3\xcf\xd3\n", 'application/pdf'];
        yield 'text' => ['Hello world, this is plain text.', 'text/plain'];
    }

    #[DataProvider('provideMimeTypes')]
    #[RequiresPhpExtension('fileinfo')]
    public function testMimeType(string $content, string $expected): void
    {
        self::assertSame($expected, StreamInfo::mimeType(Stream::from($content)));
    }

    #[RequiresPhpExtension('fileinfo')]
    public function testMimeTypeReadsSeekableStreamFromStartAndRestoresPosition(): void
    {
        $stream = Stream::from('%PDF-1.4');
        $stream->seek(3);

        self::assertSame('application/pdf', StreamInfo::mimeType($stream));
        self::assertSame(3, $stream->tell());
    }

    #[RequiresPhpExtension('fileinfo')]
    public function testMimeTypeOfNonSeekableStream(): void
    {
        self::assertSame('application/pdf', StreamInfo::mimeType($this->nonSeekable('%PDF-1.4')));
    }

    public function testMimeTypeThrowsOnUnreadableStream(): void
    {
        $this->expectException(StreamException::class);
        StreamInfo::mimeType($this->unreadable());
    }

    private function unreadable(): StreamInterface
    {
        return new class (Stream::from('hello world')) implements StreamInterface {
            use StreamDecoratorTrait;

            public function isReadable(): bool
            {
                return false;
            }
        };
    }

    private function nonSeekable(string $content): StreamInterface
    {
        return new class (Stream::from($content)) implements StreamInterface {
            use StreamDecoratorTrait;

            public function isSeekable(): bool
            {
                return false;
            }
        };
    }
}
