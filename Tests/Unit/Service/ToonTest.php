<?php

declare(strict_types=1);

namespace RRP\T3Toon\Tests\Unit\Service;

use RRP\T3Toon\Service\Toon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ToonTest extends UnitTestCase
{
    private Toon $toon;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock ExtensionConfiguration to avoid dependency issues in ToonHelper
        $extensionConfigurationMock = $this->createMock(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class);
        $extensionConfigurationMock->method('get')->willReturn([]);
        GeneralUtility::addInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class, $extensionConfigurationMock);

        $this->toon = new Toon();
    }

    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    public function testConvertSimpleArrayToToon(): void
    {
        $input = [
            'name' => 'TYPO3',
            'version' => 13,
        ];

        $toon = $this->toon->convert($input);

        self::assertIsString($toon);
        self::assertStringContainsString('name: TYPO3', $toon);
    }

    public function testDecodeToonToArray(): void
    {
        $toonString = <<<TOON
name: TYPO3
version: 13
TOON;

        $decoded = $this->toon->decode($toonString);

        self::assertSame('TYPO3', $decoded['name']);
        self::assertSame(13, $decoded['version']);
    }
}
