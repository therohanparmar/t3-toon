<?php

declare(strict_types=1);

namespace RRP\T3Toon\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use RRP\T3Toon\Domain\Model\DecodeOptions;

final class DecodeOptionsTest extends TestCase
{
    public function testDefaultReturnsEmptyOverrides(): void
    {
        self::assertSame([], DecodeOptions::default()->toConfigOverrides());
    }

    public function testLenient(): void
    {
        $options = DecodeOptions::lenient();
        self::assertFalse($options->strict);
        self::assertSame(['strict' => false], $options->toConfigOverrides());
    }

    public function testExpanded(): void
    {
        $options = DecodeOptions::expanded();
        self::assertSame('safe', $options->expandPaths);
        self::assertSame(['expand_paths' => 'safe'], $options->toConfigOverrides());
    }

    public function testConstructorOverrides(): void
    {
        $options = new DecodeOptions(strict: false, expandPaths: 'safe');
        self::assertSame(['strict' => false, 'expand_paths' => 'safe'], $options->toConfigOverrides());
    }

    public function testInvalidExpandPathsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DecodeOptions(expandPaths: 'deep');
    }
}
