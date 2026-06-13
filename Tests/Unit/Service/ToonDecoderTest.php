<?php

declare(strict_types=1);

namespace RRP\T3Toon\Tests\Unit\Service;

use RRP\T3Toon\Domain\Model\DecodeOptions;
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

    public function testSimpleKeyValue(): void
    {
        $decoded = $this->decoder->fromToon("name: TYPO3\nversion: 13");
        self::assertSame(['name' => 'TYPO3', 'version' => 13], $decoded);
    }

    public function testNested(): void
    {
        $decoded = $this->decoder->fromToon("user: Alice\nmeta:\n  active: true\n  score: 9.5");
        self::assertSame(['user' => 'Alice', 'meta' => ['active' => true, 'score' => 9.5]], $decoded);
    }

    public function testTabularItems(): void
    {
        $decoded = $this->decoder->fromToon("items[2]{id,name}:\n  1,Alice\n  2,Bob");
        self::assertSame(
            ['items' => [['id' => 1, 'name' => 'Alice'], ['id' => 2, 'name' => 'Bob']]],
            $decoded,
        );
    }

    public function testCoercesTypes(): void
    {
        $decoded = $this->decoder->fromToon("flag: true\ncount: 42\nempty: null");
        self::assertSame(['flag' => true, 'count' => 42, 'empty' => null], $decoded);
    }

    public function testRootArrayAndScalar(): void
    {
        self::assertSame(['x', 'y'], $this->decoder->fromToon('[2]: x,y'));
        self::assertSame(42, $this->decoder->fromToon('42'));
        self::assertSame([], $this->decoder->fromToon('[]'));
    }

    public function testStrictCountMismatchThrows(): void
    {
        $this->expectException(ToonDecodeException::class);
        $this->decoder->fromToon('tags[3]: a,b');
    }

    public function testDecodeExceptionHasLineNumberAndSnippet(): void
    {
        try {
            $this->decoder->fromToon("items[2]{id,name}:\n  1,Ada\n  2");
            self::fail('Expected ToonDecodeException');
        } catch (ToonDecodeException $e) {
            self::assertSame(3, $e->getLineNumber());
            self::assertSame('2', $e->getSnippet());
        }
    }

    public function testLenientAcceptsCountMismatch(): void
    {
        $decoded = $this->decoder->fromToon('tags[3]: a,b', DecodeOptions::lenient());
        self::assertSame(['tags' => ['a', 'b']], $decoded);
    }

    public function testPathExpansionOption(): void
    {
        $decoded = $this->decoder->fromToon('a.b.c: 1', DecodeOptions::expanded());
        self::assertSame(['a' => ['b' => ['c' => 1]]], $decoded);
    }

    public function testBlankLinesBetweenFieldsSkipped(): void
    {
        $decoded = $this->decoder->fromToon("a: 1\n\n\nb: 2");
        self::assertSame(['a' => 1, 'b' => 2], $decoded);
    }
}
