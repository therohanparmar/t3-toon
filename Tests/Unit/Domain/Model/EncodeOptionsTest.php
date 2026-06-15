<?php

declare(strict_types=1);

namespace RRP\T3Toon\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use RRP\T3Toon\Domain\Model\EncodeOptions;

final class EncodeOptionsTest extends TestCase
{
    public function testDefaultReturnsEmptyOverrides(): void
    {
        self::assertSame([], EncodeOptions::default()->toConfigOverrides());
    }

    public function testCompact(): void
    {
        $options = EncodeOptions::compact();
        self::assertSame(2, $options->indent);
        self::assertSame(',', $options->delimiter);
        $overrides = $options->toConfigOverrides();
        self::assertSame(2, $overrides['indent']);
        self::assertSame(',', $overrides['delimiter']);
    }

    public function testReadable(): void
    {
        self::assertSame(4, EncodeOptions::readable()->indent);
    }

    public function testTabular(): void
    {
        self::assertSame("\t", EncodeOptions::tabular()->delimiter);
    }

    public function testFolded(): void
    {
        $options = EncodeOptions::folded();
        self::assertSame('safe', $options->keyFolding);
        self::assertSame(['key_folding' => 'safe'], $options->toConfigOverrides());
    }

    public function testConstructorOverrides(): void
    {
        $options = new EncodeOptions(indent: 4, delimiter: '|', keyFolding: 'safe', flattenDepth: 2);
        self::assertSame(
            ['indent' => 4, 'delimiter' => '|', 'key_folding' => 'safe', 'flatten_depth' => 2],
            $options->toConfigOverrides(),
        );
    }

    public function testPipeDelimiterIsValid(): void
    {
        self::assertSame('|', (new EncodeOptions(delimiter: '|'))->delimiter);
    }

    public function testIndentBelowOneThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('indent must be >= 1');
        new EncodeOptions(indent: 0);
    }

    public function testInvalidDelimiterThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('delimiter');
        new EncodeOptions(delimiter: ';');
    }

    public function testInvalidKeyFoldingThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new EncodeOptions(keyFolding: 'always');
    }
}
