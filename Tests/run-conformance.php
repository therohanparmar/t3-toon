<?php

declare(strict_types=1);

/**
 * Standalone TOON conformance runner (no PHPUnit / composer required).
 *
 * Loads the vendored language-agnostic spec fixtures and runs them against the
 * spec Encoder/Decoder. Reports pass/fail per file and an overall summary.
 *
 * Usage:
 *   php Tests/run-conformance.php            # run everything
 *   php Tests/run-conformance.php encode     # only encode fixtures
 *   php Tests/run-conformance.php decode     # only decode fixtures
 *   php Tests/run-conformance.php -v         # verbose: print each failure
 */

require __DIR__ . '/../Classes/Spec/Encoder.php';
foreach (['DecodeException', 'RawObject', 'Decoder'] as $cls) {
    $f = __DIR__ . "/../Classes/Spec/$cls.php";
    if (is_file($f)) {
        require $f;
    }
}

use RRP\T3Toon\Spec\Encoder;
use RRP\T3Toon\Spec\Decoder;

$args = array_slice($argv, 1);
$verbose = in_array('-v', $args, true);
$only = null;
foreach ($args as $a) {
    if ($a === 'encode' || $a === 'decode') {
        $only = $a;
    }
}

$fixturesDir = __DIR__ . '/Fixtures/spec';
$totalPass = 0;
$totalFail = 0;
$fileSummaries = [];

/** Re-encode a value to canonical JSON for structural comparison. */
function canonicalJson(mixed $v): string
{
    return json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function buildEncoder(array $opts): Encoder
{
    return new Encoder(
        indent: $opts['indent'] ?? 2,
        delimiter: $opts['delimiter'] ?? ',',
        keyFolding: $opts['keyFolding'] ?? 'off',
        flattenDepth: array_key_exists('flattenDepth', $opts) ? $opts['flattenDepth'] : null,
    );
}

function buildDecoder(array $opts): Decoder
{
    return new Decoder(
        indent: $opts['indent'] ?? 2,
        strict: $opts['strict'] ?? true,
        expandPaths: $opts['expandPaths'] ?? 'off',
    );
}

$categories = $only ? [$only] : ['encode', 'decode'];

foreach ($categories as $category) {
    $dir = "$fixturesDir/$category";
    if (!is_dir($dir)) {
        continue;
    }
    if ($category === 'decode' && !class_exists(Decoder::class)) {
        echo "(decoder not implemented yet — skipping decode fixtures)\n";
        continue;
    }

    foreach (glob("$dir/*.json") as $file) {
        $data = json_decode((string) file_get_contents($file), false);
        $pass = 0;
        $fail = 0;
        $failures = [];

        foreach ($data->tests as $test) {
            $opts = isset($test->options) ? (array) $test->options : [];
            // Normalize option object -> array (flattenDepth may be present).
            $shouldError = $test->shouldError ?? false;
            $name = $test->name;

            try {
                if ($category === 'encode') {
                    $actual = buildEncoder($opts)->encode($test->input);
                    $expected = $test->expected;
                    $ok = !$shouldError && $actual === $expected;
                    if ($shouldError) {
                        $ok = false; // encoder produced output but error was expected
                    }
                    $detail = $ok ? '' : sprintf("\n    expected: %s\n    actual:   %s", json_encode($expected), json_encode($actual));
                } else {
                    $actual = buildDecoder($opts)->decode($test->input);
                    if ($shouldError) {
                        $ok = false; // no exception thrown but one was expected
                        $detail = "\n    expected an error, got: " . canonicalJson($actual);
                    } else {
                        $expJson = canonicalJson($test->expected);
                        $actJson = canonicalJson($actual);
                        $ok = $expJson === $actJson;
                        $detail = $ok ? '' : sprintf("\n    expected: %s\n    actual:   %s", $expJson, $actJson);
                    }
                }
            } catch (\Throwable $e) {
                if ($shouldError) {
                    $ok = true;
                    $detail = '';
                } else {
                    $ok = false;
                    $detail = "\n    threw: " . get_class($e) . ': ' . $e->getMessage();
                }
            }

            if ($ok) {
                $pass++;
            } else {
                $fail++;
                $failures[] = "  ✗ $name$detail";
            }
        }

        $totalPass += $pass;
        $totalFail += $fail;
        $base = $category . '/' . basename($file);
        $fileSummaries[] = sprintf('%-45s %3d/%-3d', $base, $pass, $pass + $fail);
        if ($verbose && $failures) {
            echo "\n$base:\n" . implode("\n", $failures) . "\n";
        }
    }
}

echo "\n";
foreach ($fileSummaries as $line) {
    echo $line . "\n";
}
echo str_repeat('-', 55) . "\n";
printf("TOTAL: %d passed, %d failed (%d total)\n", $totalPass, $totalFail, $totalPass + $totalFail);

exit($totalFail > 0 ? 1 : 0);
