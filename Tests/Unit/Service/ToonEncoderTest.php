<?php

declare(strict_types=1);

namespace RRP\T3Toon\Tests\Unit\Service;

use RRP\T3Toon\Domain\Model\EncodeOptions;
use RRP\T3Toon\Service\ToonEncoder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ToonEncoderTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $extensionConfigurationMock = $this->createMock(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class);
        $extensionConfigurationMock->method('get')->willReturn([]);
        GeneralUtility::addInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class, $extensionConfigurationMock);
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    public function testToToonSimpleAssociativeArray(): void
    {
        $encoder = new ToonEncoder();
        $input = ['name' => 'TYPO3', 'version' => 13];
        $toon = $encoder->toToon($input);
        self::assertIsString($toon);
        self::assertStringContainsString('name: TYPO3', $toon);
        self::assertStringContainsString('version: 13', $toon);
    }

    public function testToToonNestedStructure(): void
    {
        $encoder = new ToonEncoder();
        $input = [
            'user' => 'Alice',
            'meta' => [
                'active' => true,
                'score' => 9.5,
            ],
        ];
        $toon = $encoder->toToon($input);
        self::assertStringContainsString('user: Alice', $toon);
        self::assertStringContainsString('meta:', $toon);
        self::assertStringContainsString('active: true', $toon);
        self::assertStringContainsString('score: 9.5', $toon);
    }

    public function testToToonJsonStringInput(): void
    {
        $encoder = new ToonEncoder();
        $json = '{"a": 1, "b": 2}';
        $toon = $encoder->toToon($json);
        self::assertStringContainsString('a: 1', $toon);
        self::assertStringContainsString('b: 2', $toon);
    }

    public function testToToonScalarInput(): void
    {
        $encoder = new ToonEncoder();
        self::assertSame('hello', $encoder->toToon('hello'));
        self::assertSame('42', $encoder->toToon(42));
        // Top-level bool is cast to string by toToon(): true -> "1", false -> ""
        self::assertSame('1', $encoder->toToon(true));
        self::assertSame('', $encoder->toToon(false));
        // When inside array, bool is rendered as true/false
        self::assertStringContainsString('true', $encoder->toToon(['x' => true]));
        self::assertStringContainsString('false', $encoder->toToon(['x' => false]));
    }

    public function testToToonTabularUniformObjects(): void
    {
        $encoder = new ToonEncoder();
        $input = [
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
        ];
        $toon = $encoder->toToon($input);
        self::assertStringContainsString('items[2]{id,name}:', $toon);
        self::assertStringContainsString('1,A', $toon);
        self::assertStringContainsString('2,B', $toon);
    }

    public function testToToonObjectInputConvertedToArray(): void
    {
        $encoder = new ToonEncoder();
        $obj = (object) ['x' => 1, 'y' => 2];
        $toon = $encoder->toToon($obj);
        self::assertStringContainsString('x: 1', $toon);
        self::assertStringContainsString('y: 2', $toon);
    }

    public function testToToonWithEncodeOptionsIndent(): void
    {
        $encoder = new ToonEncoder();
        $input = ['a' => ['b' => 'c']];
        $default = $encoder->toToon($input);
        $readable = $encoder->toToon($input, EncodeOptions::readable());
        // Readable uses 4 spaces per level
        self::assertStringContainsString('b: c', $readable);
        self::assertNotEquals($default, $readable);
    }

    public function testToToonWithPrimitiveArrayHeader(): void
    {
        $encoder = new ToonEncoder();
        $input = ['tags' => ['a', 'b', 'c']];
        $options = new EncodeOptions(primitiveArrayHeader: true);
        $toon = $encoder->toToon($input, $options);
        self::assertStringContainsString('tags[3]:', $toon);
        self::assertStringContainsString('a,b,c', $toon);
    }

    public function testToToonWithEncodeOptionsTabularDelimiter(): void
    {
        $encoder = new ToonEncoder();
        $input = [
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
        ];
        $toon = $encoder->toToon($input, EncodeOptions::tabular());
        self::assertStringContainsString('items[2]{id,name}:', $toon);
        self::assertStringContainsString("\t", $toon);
        self::assertStringNotContainsString('1,A', $toon);
    }
}
