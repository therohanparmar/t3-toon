<?php

declare(strict_types=1);

namespace RRP\T3Toon\Tests\Unit\Service;

use RRP\T3Toon\Exception\ToonDecodeException;
use RRP\T3Toon\Service\ToonDecoder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ToonDecoderTest extends UnitTestCase
{
    private ToonDecoder $decoder;

    protected function setUp(): void
    {
        parent::setUp();
        $extensionConfigurationMock = $this->createMock(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class);
        $extensionConfigurationMock->method('get')->willReturn([]);
        GeneralUtility::addInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class, $extensionConfigurationMock);
        $this->decoder = new ToonDecoder();
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    public function testFromToonSimpleKeyValue(): void
    {
        $toon = "name: TYPO3\nversion: 13";
        $decoded = $this->decoder->fromToon($toon);
        self::assertSame('TYPO3', $decoded['name']);
        self::assertSame(13, $decoded['version']);
    }

    public function testFromToonNested(): void
    {
        $toon = <<<TOON
user: Alice
meta:
  active: true
  score: 9.5
TOON;
        $decoded = $this->decoder->fromToon($toon);
        self::assertSame('Alice', $decoded['user']);
        self::assertIsArray($decoded['meta']);
        self::assertTrue($decoded['meta']['active']);
        self::assertSame(9.5, $decoded['meta']['score']);
    }

    public function testFromToonTabularItems(): void
    {
        $toon = <<<TOON
items[2]{id,name}:
  1,Alice
  2,Bob
TOON;
        $decoded = $this->decoder->fromToon($toon);
        self::assertIsArray($decoded);
        self::assertCount(1, $decoded);
        self::assertIsArray($decoded[0]);
        self::assertSame([['id' => 1, 'name' => 'Alice'], ['id' => 2, 'name' => 'Bob']], $decoded[0]);
    }

    public function testFromToonCoercesTypes(): void
    {
        $toon = "flag: true\ncount: 42\nempty: null";
        $decoded = $this->decoder->fromToon($toon);
        self::assertTrue($decoded['flag']);
        self::assertSame(42, $decoded['count']);
        self::assertNull($decoded['empty']);
    }

    public function testFromToonMalformedLineThrowsToonDecodeException(): void
    {
        // Line with colon but invalid key:value format (space before colon)
        $toon = "valid: key\nkey : value";
        $this->expectException(ToonDecodeException::class);
        $this->expectExceptionMessage('Line 2');
        $this->expectExceptionMessage('Invalid key:value format');
        $this->decoder->fromToon($toon);
    }

    public function testFromToonDecodeExceptionHasLineNumberAndSnippet(): void
    {
        // Invalid key:value format triggers exception with line number and snippet
        $toon = "valid: key\nbad : snippet";
        try {
            $this->decoder->fromToon($toon);
            self::fail('Expected ToonDecodeException');
        } catch (ToonDecodeException $e) {
            self::assertSame(2, $e->getLineNumber());
            self::assertSame('bad : snippet', $e->getSnippet());
        }
    }

    public function testFromToonBlankLinesSkipped(): void
    {
        $toon = "a: 1\n\n\nb: 2";
        $decoded = $this->decoder->fromToon($toon);
        self::assertSame(1, $decoded['a']);
        self::assertSame(2, $decoded['b']);
    }
}
