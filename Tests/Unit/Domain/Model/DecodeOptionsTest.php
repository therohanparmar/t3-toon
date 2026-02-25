<?php

declare(strict_types=1);

namespace RRP\T3Toon\Tests\Unit\Domain\Model;

use RRP\T3Toon\Domain\Model\DecodeOptions;
use PHPUnit\Framework\TestCase;

final class DecodeOptionsTest extends TestCase
{
    public function testDefaultReturnsEmptyOverrides(): void
    {
        $options = DecodeOptions::default();
        self::assertSame([], $options->toConfigOverrides());
    }

    public function testLenient(): void
    {
        $options = DecodeOptions::lenient();
        self::assertFalse($options->coerceScalarTypes);
        $overrides = $options->toConfigOverrides();
        self::assertFalse($overrides['coerce_scalar_types']);
    }

    public function testConstructorWithCoerceScalarTypes(): void
    {
        $options = new DecodeOptions(coerceScalarTypes: false);
        self::assertSame(['coerce_scalar_types' => false], $options->toConfigOverrides());
    }
}
