<?php

declare(strict_types=1);

namespace RRP\T3Toon\Tests\Unit\Service;

use RRP\T3Toon\Domain\Model\DecodeOptions;
use RRP\T3Toon\Domain\Model\EncodeOptions;
use RRP\T3Toon\Exception\ToonDecodeException;
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
        $extensionConfigurationMock->method('get')->willReturnCallback(
            static fn (string $extensionKey, string $path = ''): mixed => match (true) {
                $extensionKey !== 'rrp_t3toon' => [],
                $path === '' => ['enabled' => '1', 'indent' => '2'],
                $path === 'enabled' => '1',
                default => [],
            },
        );
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

    public function testStaticEncodeAndConvert(): void
    {
        $input = ['a' => 1, 'b' => 2];
        self::assertSame($this->toon->encode($input), Toon::encodeStatic($input));
        self::assertSame($this->toon->convert($input), Toon::convertStatic($input));
        self::assertStringContainsString('a: 1', Toon::encodeStatic($input));
    }

    public function testStaticDecode(): void
    {
        $toon = "x: 1\ny: 2";
        $decoded = Toon::decodeStatic($toon);
        self::assertSame(1, $decoded['x']);
        self::assertSame(2, $decoded['y']);
    }

    public function testStaticEstimateTokens(): void
    {
        $toon = "name: TYPO3\nversion: 13";
        $result = Toon::estimateTokensStatic($toon);
        self::assertArrayHasKey('words', $result);
        self::assertArrayHasKey('chars', $result);
        self::assertArrayHasKey('tokens_estimate', $result);
        self::assertGreaterThanOrEqual(1, $result['tokens_estimate']);
    }

    public function testRoundTripEncodeDecode(): void
    {
        $input = [
            'user' => 'ABC',
            'tasks' => [
                ['id' => 1, 'done' => false],
                ['id' => 2, 'done' => true],
            ],
        ];
        $toon = $this->toon->encode($input);
        $decoded = $this->toon->decode($toon);
        // tasks is a uniform array of objects -> tabular -> array of rows.
        self::assertSame($input, $decoded);
        self::assertSame(1, $decoded['tasks'][0]['id']);
        self::assertFalse($decoded['tasks'][0]['done']);
        self::assertSame(2, $decoded['tasks'][1]['id']);
        self::assertTrue($decoded['tasks'][1]['done']);
    }

    public function testDecodeMalformedThrowsToonDecodeException(): void
    {
        $this->expectException(ToonDecodeException::class);
        // Inline array length mismatch (declared 3, only 1 value).
        Toon::decodeStatic('tags[3]: a');
    }

    public function testEncodeWithEncodeOptionsIndent(): void
    {
        $input = ['a' => ['b' => 'c']];
        $default = $this->toon->encode($input);
        $readable = $this->toon->encode($input, EncodeOptions::readable());
        // Readable uses 4 spaces; default uses 2. So readable has more spaces.
        self::assertStringContainsString('b: c', $readable);
        self::assertNotEquals($default, $readable);
    }

    public function testDecodeWithDecodeOptionsLenient(): void
    {
        // Type coercion follows the spec in both modes.
        $decoded = $this->toon->decode("flag: true\ncount: 42");
        self::assertTrue($decoded['flag']);
        self::assertSame(42, $decoded['count']);

        // Lenient = non-strict: tolerates an array length mismatch that strict mode rejects.
        $lenient = $this->toon->decode('tags[3]: a,b', DecodeOptions::lenient());
        self::assertSame(['a', 'b'], $lenient['tags']);
    }
}
