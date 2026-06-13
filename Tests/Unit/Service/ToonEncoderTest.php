<?php

declare(strict_types=1);

namespace RRP\T3Toon\Tests\Unit\Service;

use RRP\T3Toon\Domain\Model\EncodeOptions;
use RRP\T3Toon\Service\ToonEncoder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ToonEncoderTest extends UnitTestCase
{
    private ToonEncoder $encoder;

    protected function setUp(): void
    {
        parent::setUp();
        $extensionConfigurationMock = $this->createMock(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class);
        $extensionConfigurationMock->method('get')->willReturn([]);
        GeneralUtility::addInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class, $extensionConfigurationMock);
        $this->encoder = new ToonEncoder();
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    public function testSimpleAssociativeArray(): void
    {
        self::assertSame(
            "name: TYPO3\nversion: 13",
            $this->encoder->toToon(['name' => 'TYPO3', 'version' => 13]),
        );
    }

    public function testNestedStructureAndInlineArray(): void
    {
        $toon = $this->encoder->toToon([
            'user' => 'Alice',
            'meta' => ['active' => true, 'score' => 9.5],
            'tags' => ['a', 'b'],
        ]);
        self::assertSame("user: Alice\nmeta:\n  active: true\n  score: 9.5\ntags[2]: a,b", $toon);
    }

    public function testJsonStringInput(): void
    {
        self::assertSame("a: 1\nb: 2", $this->encoder->toToon('{"a": 1, "b": 2}'));
    }

    public function testScalarInput(): void
    {
        self::assertSame('hello', $this->encoder->toToon('hello'));
        self::assertSame('42', $this->encoder->toToon(42));
        self::assertSame('true', $this->encoder->toToon(true));
        self::assertSame('false', $this->encoder->toToon(false));
        self::assertSame('null', $this->encoder->toToon(null));
        // Strings that look like primitives must be quoted (§7.2).
        self::assertSame('"true"', $this->encoder->toToon('true'));
        self::assertSame('"42"', $this->encoder->toToon('42'));
    }

    public function testRootTabularUniformObjects(): void
    {
        $toon = $this->encoder->toToon([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
        ]);
        self::assertSame("[2]{id,name}:\n  1,A\n  2,B", $toon);
    }

    public function testKeyedTabularUsesRealKey(): void
    {
        $toon = $this->encoder->toToon(['rows' => [['id' => 1], ['id' => 2]]]);
        self::assertSame("rows[2]{id}:\n  1\n  2", $toon);
    }

    public function testObjectInputPreservesKeys(): void
    {
        $toon = $this->encoder->toToon((object) ['x' => 1, 'y' => 2]);
        self::assertSame("x: 1\ny: 2", $toon);
    }

    public function testReadableIndentOption(): void
    {
        $toon = $this->encoder->toToon(['a' => ['b' => 'c']], EncodeOptions::readable());
        self::assertSame("a:\n    b: c", $toon);
    }

    public function testTabularDelimiterOption(): void
    {
        $toon = $this->encoder->toToon([['id' => 1, 'name' => 'A']], EncodeOptions::tabular());
        self::assertSame("[1\t]{id\tname}:\n  1\tA", $toon);
    }

    public function testKeyFoldingOption(): void
    {
        self::assertSame(
            'a.b.c: 1',
            $this->encoder->toToon(['a' => ['b' => ['c' => 1]]], EncodeOptions::folded()),
        );
    }
}
