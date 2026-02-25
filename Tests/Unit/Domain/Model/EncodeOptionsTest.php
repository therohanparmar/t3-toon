<?php

declare(strict_types=1);

namespace RRP\T3Toon\Tests\Unit\Domain\Model;

use RRP\T3Toon\Domain\Model\EncodeOptions;
use PHPUnit\Framework\TestCase;

final class EncodeOptionsTest extends TestCase
{
    public function testDefaultReturnsEmptyOverrides(): void
    {
        $options = EncodeOptions::default();
        self::assertSame([], $options->toConfigOverrides());
    }

    public function testCompact(): void
    {
        $options = EncodeOptions::compact();
        self::assertSame(0, $options->indent);
        self::assertSame(',', $options->delimiter);
        $overrides = $options->toConfigOverrides();
        self::assertSame(0, $overrides['indent']);
        self::assertSame(',', $overrides['delimiter']);
    }

    public function testReadable(): void
    {
        $options = EncodeOptions::readable();
        self::assertSame(4, $options->indent);
    }

    public function testTabular(): void
    {
        $options = EncodeOptions::tabular();
        self::assertSame("\t", $options->delimiter);
    }

    public function testConstructorWithIndentAndMaxPreviewItems(): void
    {
        $options = new EncodeOptions(indent: 4, maxPreviewItems: 10);
        $overrides = $options->toConfigOverrides();
        self::assertSame(4, $overrides['indent']);
        self::assertSame(10, $overrides['max_preview_items']);
    }

    public function testNegativeIndentThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('non-negative');
        new EncodeOptions(indent: -1);
    }

    public function testInvalidDelimiterThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('delimiter');
        new EncodeOptions(delimiter: '|');
    }
}
