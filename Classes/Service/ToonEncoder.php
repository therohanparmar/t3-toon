<?php

declare(strict_types=1);

namespace RRP\T3Toon\Service;

use RRP\T3Toon\Domain\Model\EncodeOptions;
use RRP\T3Toon\Spec\Encoder;
use RRP\T3Toon\Utility\ToonHelper;

/**
 * TYPO3 adapter around the spec-compliant {@see Encoder}.
 *
 * Normalizes host input (JSON strings, objects, arrays, scalars) to the JSON
 * data model, then delegates to the spec encoder using the effective extension
 * configuration (optionally overridden per call via {@see EncodeOptions}).
 */
class ToonEncoder
{
    /**
     * Convert input into spec-compliant TOON.
     *
     * @param mixed $input Array, object, JSON string, or scalar.
     * @param EncodeOptions|null $options Per-call overrides; null = extension config.
     */
    public function toToon(mixed $input, ?EncodeOptions $options = null): string
    {
        $config = ToonHelper::getConfigMerged($options?->toConfigOverrides() ?? []);

        $encoder = new Encoder(
            indent: (int) $config['indent'],
            delimiter: (string) $config['delimiter'],
            keyFolding: (string) $config['key_folding'],
            flattenDepth: $config['flatten_depth'],
        );

        return $encoder->encode($this->normalizeInput($input));
    }

    /**
     * Map host input to the JSON data model the Spec encoder expects.
     * Objects become \stdClass (preserving object vs array distinction).
     */
    private function normalizeInput(mixed $input): mixed
    {
        if (is_string($input) && $this->looksLikeJson($input)) {
            $decoded = json_decode($input);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            return $input; // not valid JSON after all: treat as a plain string value
        }

        if (is_object($input)) {
            // Normalize arbitrary objects to stdClass/array trees, preserving structure.
            return json_decode(json_encode($input, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: 'null');
        }

        return $input;
    }

    private function looksLikeJson(string $s): bool
    {
        $s = ltrim($s);
        return $s !== '' && ($s[0] === '{' || $s[0] === '[');
    }
}
