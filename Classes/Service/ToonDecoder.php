<?php

declare(strict_types=1);

namespace RRP\T3Toon\Service;

use RRP\T3Toon\Domain\Model\DecodeOptions;
use RRP\T3Toon\Exception\ToonDecodeException;
use RRP\T3Toon\Spec\Decoder;
use RRP\T3Toon\Spec\DecodeException;
use RRP\T3Toon\Utility\ToonHelper;

/**
 * TYPO3 adapter around the spec-compliant {@see Decoder}.
 *
 * Delegates to the spec decoder using the effective extension configuration
 * (optionally overridden per call via {@see DecodeOptions}), then converts the
 * decoded JSON data model into native PHP values: objects become associative
 * arrays, arrays stay lists, primitives stay scalars.
 */
class ToonDecoder
{
    /**
     * Decode a TOON string.
     *
     * @return mixed Associative array for objects, list for arrays, or a scalar/null at the root.
     * @throws ToonDecodeException When the TOON input is malformed or violates strict mode.
     */
    public function fromToon(string $toon, ?DecodeOptions $options = null): mixed
    {
        $config = ToonHelper::getConfigMerged($options?->toConfigOverrides() ?? []);

        $decoder = new Decoder(
            indent: (int) $config['indent'],
            strict: (bool) $config['strict'],
            expandPaths: (string) $config['expand_paths'],
        );

        try {
            $result = $decoder->decode($toon);
        } catch (DecodeException $e) {
            // Map the dependency-free spec exception onto the public TYPO3 exception.
            throw new ToonDecodeException($e->getMessage(), $e->lineNumber ?? 0, $e->snippet, $e);
        }

        return $this->toNative($result);
    }

    /** Recursively convert \stdClass objects to ordered associative arrays. */
    private function toNative(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            $out = [];
            foreach (get_object_vars($value) as $k => $v) {
                $out[$k] = $this->toNative($v);
            }
            return $out;
        }
        if (is_array($value)) {
            return array_map(fn($v) => $this->toNative($v), $value);
        }
        return $value;
    }
}
