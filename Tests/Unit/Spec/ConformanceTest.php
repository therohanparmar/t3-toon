<?php

declare(strict_types=1);

namespace RRP\T3Toon\Tests\Unit\Spec;

use PHPUnit\Framework\TestCase;
use RRP\T3Toon\Spec\Decoder;
use RRP\T3Toon\Spec\Encoder;

/**
 * Runs the official language-agnostic TOON conformance fixtures
 * (vendored under Tests/Fixtures/spec/, see PROVENANCE.txt) against the
 * spec Encoder and Decoder.
 *
 * The Spec engine is dependency-free, so this test needs no TYPO3 bootstrap.
 */
final class ConformanceTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../../Fixtures/spec';

    /**
     * @dataProvider encodeProvider
     */
    public function testEncode(string $name, mixed $input, mixed $expected, array $options, bool $shouldError): void
    {
        $encoder = new Encoder(
            indent: $options['indent'] ?? 2,
            delimiter: $options['delimiter'] ?? ',',
            keyFolding: $options['keyFolding'] ?? 'off',
            flattenDepth: array_key_exists('flattenDepth', $options) ? $options['flattenDepth'] : null,
        );

        if ($shouldError) {
            $errored = false;
            try {
                $encoder->encode($input);
            } catch (\Throwable) {
                $errored = true;
            }
            self::assertTrue($errored, "$name: expected an encoder error");
            return;
        }

        self::assertSame($expected, $encoder->encode($input), $name);
    }

    /**
     * @dataProvider decodeProvider
     */
    public function testDecode(string $name, mixed $input, mixed $expected, array $options, bool $shouldError): void
    {
        $decoder = new Decoder(
            indent: $options['indent'] ?? 2,
            strict: $options['strict'] ?? true,
            expandPaths: $options['expandPaths'] ?? 'off',
        );

        if ($shouldError) {
            $this->expectException(\Throwable::class);
            $decoder->decode($input);
            return;
        }

        $actual = $decoder->decode($input);
        self::assertSame($this->canonical($expected), $this->canonical($actual), $name);
    }

    public static function encodeProvider(): iterable
    {
        return self::load('encode');
    }

    public static function decodeProvider(): iterable
    {
        return self::load('decode');
    }

    /** @return iterable<string, array{0:string,1:mixed,2:mixed,3:array,4:bool}> */
    private static function load(string $category): iterable
    {
        foreach (glob(self::FIXTURES . "/$category/*.json") as $file) {
            $data = json_decode((string) file_get_contents($file), false);
            foreach ($data->tests as $test) {
                $key = basename($file, '.json') . ': ' . $test->name;
                yield $key => [
                    $test->name,
                    $test->input,
                    $test->expected,
                    isset($test->options) ? (array) $test->options : [],
                    $test->shouldError ?? false,
                ];
            }
        }
    }

    private function canonical(mixed $v): string
    {
        return json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
