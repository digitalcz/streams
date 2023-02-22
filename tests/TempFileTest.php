<?php

declare(strict_types=1);

namespace DigitalCz\Streams;

use PHPUnit\Framework\TestCase;

/**
 * @covers \DigitalCz\Streams\TempFile
 */
class TempFileTest extends TestCase
{
    public function testCreateAndDelete(): void
    {
        $file = new TempFile();
        self::assertFileExists($file->getPath());
        $file->delete();
        self::assertFileDoesNotExist($file->getPath());
    }
}
