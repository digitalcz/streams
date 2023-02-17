<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

interface FileInterface extends StreamInterface
{
    public function getPath(): string;

    public function delete(): void;
}
