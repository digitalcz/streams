<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use InvalidArgumentException;

/**
 * @see https://www.php.net/streamwrapper
 */
final class StreamWrapper
{
    /** @var resource */
    public $context;
    private StreamInterface $stream;
    private string $mode;

    /**
     * Returns a resource representing the stream.
     *
     * @return resource
     *
     * @throws InvalidArgumentException if stream is not readable or writable
     */
    public static function from(StreamInterface $stream)
    {
        self::register();

        if ($stream->isReadable()) {
            $mode = $stream->isWritable() ? 'r+' : 'r';
        } elseif ($stream->isWritable()) {
            $mode = 'w';
        } else {
            throw new InvalidArgumentException('The stream must be readable, writable, or both.');
        }

        $resource = fopen('digitalcz-streams://stream', $mode, false, self::createStreamContext($stream));

        if (!is_resource($resource)) {
            throw new StreamException('Unable to create StreamWrapper');
        }

        return $resource;
    }

    /**
     * Creates a stream context that can be used to open a stream as a php stream resource.
     *
     * @return resource
     */
    public static function createStreamContext(StreamInterface $stream)
    {
        return stream_context_create([
            'digitalcz-streams' => ['stream' => $stream],
        ]);
    }

    /**
     * Registers the stream wrapper if needed
     */
    public static function register(): void
    {
        if (!in_array('digitalcz-streams', stream_get_wrappers(), true)) {
            stream_wrapper_register('digitalcz-streams', self::class);
        }
    }

    // phpcs:ignore
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path = null): bool
    {
        $options = stream_context_get_options($this->context);

        if (!isset($options['digitalcz-streams']['stream'])) {
            return false;
        }

        $this->mode = $mode;
        $this->stream = $options['digitalcz-streams']['stream'];

        return true;
    }

    // phpcs:ignore
    public function stream_read(int $count): string
    {
        return $this->stream->read($count);
    }

    // phpcs:ignore
    public function stream_write(string $data): int
    {
        return $this->stream->write($data);
    }

    // phpcs:ignore
    public function stream_tell(): int
    {
        return $this->stream->tell();
    }

    // phpcs:ignore
    public function stream_eof(): bool
    {
        return $this->stream->eof();
    }

    // phpcs:ignore
    public function stream_seek(int $offset, int $whence): bool
    {
        $this->stream->seek($offset, $whence);

        return true;
    }

    /**
     * @return resource|false
     */
    public function stream_cast(int $cast_as) // phpcs:ignore
    {
        $stream = clone$this->stream;
        $resource = $stream->detach();

        return $resource ?? false;
    }

    /**
     * @return array<int|string, int>
     */
    public function stream_stat(): array // phpcs:ignore
    {
        static $modeMap = [
            'r' => 33060,
            'rb' => 33060,
            'r+' => 33206,
            'w' => 33188,
            'wb' => 33188,
        ];

        return [
            'dev' => 0,
            'ino' => 0,
            'mode' => $modeMap[$this->mode],
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => $this->stream->getSize() ?? 0,
            'atime' => 0,
            'mtime' => 0,
            'ctime' => 0,
            'blksize' => 0,
            'blocks' => 0,
        ];
    }

    /**
     * @return array<int|string, int>
     */
    public function url_stat(string $path, int $flags): array // phpcs:ignore
    {
        return [
            'dev' => 0,
            'ino' => 0,
            'mode' => 0,
            'nlink' => 0,
            'uid' => 0,
            'gid' => 0,
            'rdev' => 0,
            'size' => 0,
            'atime' => 0,
            'mtime' => 0,
            'ctime' => 0,
            'blksize' => 0,
            'blocks' => 0,
        ];
    }
}
