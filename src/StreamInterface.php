<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use Psr\Http\Message\StreamInterface as PsrStreamInterface;

interface StreamInterface extends PsrStreamInterface
{
    /**
     * @param string|null $key
     * @return array<string, mixed>|mixed|null
     */
    public function getMetadata($key = null);

    public function close(): void;

    /**
     * @return resource|null
     */
    public function detach();

    public function isSeekable(): bool;

    /**
     * @param int $offset
     * @param int $whence
     */
    public function seek($offset, $whence = SEEK_SET): void;

    /**
     * @return resource
     */
    public function getHandle();

    public function getContents(): string;

    public function getSize(): ?int;

    public function isReadable(): bool;

    public function isWritable(): bool;

    public function eof(): bool;

    public function tell(): int;

    public function rewind(): void;

    /**
     * @param int $length
     */
    public function read($length): string;

    /**
     * @param string $string
     */
    public function write($string): int;

    public function copy(Stream $source): int;
}
