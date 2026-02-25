<?php

declare(strict_types=1);

namespace RRP\T3Toon\Tests\Unit\Exception;

use RRP\T3Toon\Exception\ToonDecodeException;
use RRP\T3Toon\Exception\ToonException;
use PHPUnit\Framework\TestCase;

final class ToonDecodeExceptionTest extends TestCase
{
    public function testExtendsToonException(): void
    {
        $e = new ToonDecodeException('test');
        self::assertInstanceOf(ToonException::class, $e);
    }

    public function testGetLineNumber(): void
    {
        $e = new ToonDecodeException('error', 5, null);
        self::assertSame(5, $e->getLineNumber());
    }

    public function testGetSnippet(): void
    {
        $e = new ToonDecodeException('error', 3, 'bad content');
        self::assertSame('bad content', $e->getSnippet());
    }

    public function testMessageIncludesLineAndSnippetWhenProvided(): void
    {
        $e = new ToonDecodeException('Malformed', 2, 'snippet');
        $msg = $e->getMessage();
        self::assertStringContainsString('Line 2', $msg);
        self::assertStringContainsString('Malformed', $msg);
        self::assertStringContainsString('snippet', $msg);
    }

    public function testMessageWithoutLineNumber(): void
    {
        $e = new ToonDecodeException('Generic error', 0, null);
        self::assertSame('Generic error', $e->getMessage());
    }
}
