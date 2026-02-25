<?php

declare(strict_types=1);

namespace RRP\T3Toon\Service;

use RRP\T3Toon\Domain\Model\DecodeOptions;
use RRP\T3Toon\Domain\Model\EncodeOptions;
use RRP\T3Toon\Exception\ToonDecodeException;
use RRP\T3Toon\Service\ToonEncoder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main TOON service: encode/decode PHP data to and from TOON format.
 *
 * Instance API (recommended in TYPO3 for DI):
 *   $toon = GeneralUtility::makeInstance(Toon::class);
 *   $toon->encode($data);
 *   $toon->decode($toonString);
 *
 * Static API (convenience):
 *   Toon::encodeStatic($data);
 *   Toon::decodeStatic($toonString);
 *   Toon::convertStatic($data);
 *   Toon::estimateTokensStatic($toonString);
 */
class Toon
{
    /**
     * The internal converter that handles PHP → TOON conversion.
     *
     * @var ToonEncoder
     */
    protected ToonEncoder $converter;

    /**
     * The internal decoder that handles TOON → PHP conversion.
     *
     * @var ToonDecoder
     */
    protected ToonDecoder $decoder;

    public function __construct()
    {
        $this->converter = GeneralUtility::makeInstance(ToonEncoder::class);
        $this->decoder = GeneralUtility::makeInstance(ToonDecoder::class);
    }

    /**
     * Convert arbitrary input into TOON format.
     *
     * @param mixed $input JSON, array, or object
     * @param EncodeOptions|null $options Optional encoding options; null = extension config
     * @return string TOON representation
     */
    public function convert($input, ?EncodeOptions $options = null): string
    {
        return $this->converter->toToon($input, $options);
    }

    /**
     * Encode arbitrary input into TOON format (alias for convert).
     *
     * @param mixed $input JSON, array, or object
     * @param EncodeOptions|null $options Optional encoding options; null = extension config
     * @return string TOON representation
     */
    public function encode($input, ?EncodeOptions $options = null): string
    {
        return $this->convert($input, $options);
    }

    /**
     * Decode a TOON string into an associative PHP array.
     *
     * @param string $toon TOON-formatted string
     * @param DecodeOptions|null $options Optional decoding options; null = extension config
     * @return array Decoded associative array
     * @throws ToonDecodeException When the TOON input is malformed
     */
    public function decode(string $toon, ?DecodeOptions $options = null): array
    {
        return $this->decoder->fromToon($toon, $options);
    }

    /**
     * Encode (static). Convenience without DI.
     *
     * @param mixed $input JSON, array, or object
     * @param EncodeOptions|null $options Optional encoding options; null = extension config
     * @return string TOON representation
     */
    public static function encodeStatic($input, ?EncodeOptions $options = null): string
    {
        return GeneralUtility::makeInstance(self::class)->encode($input, $options);
    }

    /**
     * Decode (static). Convenience without DI.
     *
     * @param string $toon TOON-formatted string
     * @param DecodeOptions|null $options Optional decoding options; null = extension config
     * @return array Decoded associative array
     * @throws ToonDecodeException When the TOON input is malformed
     */
    public static function decodeStatic(string $toon, ?DecodeOptions $options = null): array
    {
        return GeneralUtility::makeInstance(self::class)->decode($toon, $options);
    }

    /**
     * Convert (static). Convenience without DI.
     *
     * @param mixed $input JSON, array, or object
     * @param EncodeOptions|null $options Optional encoding options; null = extension config
     * @return string TOON representation
     */
    public static function convertStatic($input, ?EncodeOptions $options = null): string
    {
        return GeneralUtility::makeInstance(self::class)->convert($input, $options);
    }

    /**
     * Estimate tokens (static). Convenience without DI.
     *
     * @param string $toon TOON string
     * @return array{words: int, chars: int, tokens_estimate: int}
     */
    public static function estimateTokensStatic(string $toon): array
    {
        return GeneralUtility::makeInstance(self::class)->estimateTokens($toon);
    }

    /**
     * Estimate the number of tokens in a TOON string.
     *
     * This is a rough estimate based on word and character counts.
     *
     * @param string $toon
     * @return array{
     *     words: int,
     *     chars: int,
     *     tokens_estimate: int
     * }
     */
    public function estimateTokens(string $toon): array
    {
        $words = preg_split('/\s+/', trim($toon)) ?: [];
        $chars = strlen($toon);
        $tokenEstimate = max(1, (int) ceil(count($words) * 0.75 + $chars / 50));

        return [
            'words' => count($words),
            'chars' => $chars,
            'tokens_estimate' => $tokenEstimate,
        ];
    }
}