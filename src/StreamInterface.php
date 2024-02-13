<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use Psr\Http\Message\StreamInterface as PsrStreamInterface;

interface StreamInterface extends PsrStreamInterface
{
    /**
     * @return array<string, mixed>|mixed|null
     */
    public function getMetadata(?string $key = null);

    public function close(): void;

    /**
     * @return resource|null
     */
    public function detach();

    public function isSeekable(): bool;

    public function seek(int $offset, int $whence = SEEK_SET): void;

    public function getContents(): string;

    public function getSize(): ?int;

    public function isReadable(): bool;

    public function isWritable(): bool;

    public function eof(): bool;

    public function tell(): int;

    public function rewind(): void;

    public function read(int $length): string;

    public function write(string $string): int;

    public function copy(PsrStreamInterface $source): int;
}
