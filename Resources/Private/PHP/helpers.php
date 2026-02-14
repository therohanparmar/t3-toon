<?php

declare(strict_types=1);

/**
 * Global helper functions for TOON (Token-Optimized Object Notation).
 * Loaded via ext_localconf.php. Use only when TYPO3 bootstrap is available.
 */

use RRP\T3Toon\Domain\Model\DecodeOptions;
use RRP\T3Toon\Domain\Model\EncodeOptions;
use RRP\T3Toon\Service\Toon;

if (!function_exists('toon')) {
    /**
     * Encode a value to TOON format.
     *
     * @param mixed $value Data to encode (array, object, scalar)
     * @param EncodeOptions|null $options Optional encoding options; null = extension config
     * @return string TOON string
     */
    function toon($value, ?EncodeOptions $options = null): string
    {
        return Toon::encodeStatic($value, $options);
    }
}

if (!function_exists('toon_decode')) {
    /**
     * Decode a TOON string to a PHP array.
     *
     * @param string $toon TOON-formatted string
     * @param DecodeOptions|null $options Optional decoding options; null = extension config
     * @return array Decoded associative array
     * @throws \RRP\T3Toon\Exception\ToonDecodeException When the TOON input is malformed
     */
    function toon_decode(string $toon, ?DecodeOptions $options = null): array
    {
        return Toon::decodeStatic($toon, $options);
    }
}

if (!function_exists('toon_compact')) {
    /**
     * Encode to TOON with compact options (indent 0, comma delimiter).
     *
     * @param mixed $value Data to encode
     * @return string TOON string
     */
    function toon_compact($value): string
    {
        return Toon::encodeStatic($value, EncodeOptions::compact());
    }
}

if (!function_exists('toon_readable')) {
    /**
     * Encode to TOON with readable options (indent 4).
     *
     * @param mixed $value Data to encode
     * @return string TOON string
     */
    function toon_readable($value): string
    {
        return Toon::encodeStatic($value, EncodeOptions::readable());
    }
}

if (!function_exists('toon_decode_lenient')) {
    /**
     * Decode TOON without coercing scalar types (keep "true", "42" as strings).
     *
     * @param string $toon TOON string
     * @return array Decoded array
     * @throws \RRP\T3Toon\Exception\ToonDecodeException When the TOON input is malformed
     */
    function toon_decode_lenient(string $toon): array
    {
        return Toon::decodeStatic($toon, DecodeOptions::lenient());
    }
}

if (!function_exists('toon_estimate_tokens')) {
    /**
     * Estimate token count for a TOON string (words/chars heuristic).
     *
     * @param string $toon TOON string
     * @return array{words: int, chars: int, tokens_estimate: int}
     */
    function toon_estimate_tokens(string $toon): array
    {
        return Toon::estimateTokensStatic($toon);
    }
}
