<?php

declare(strict_types=1);

namespace RRP\T3Toon\Service;

use RRP\T3Toon\Domain\Model\DecodeOptions;
use RRP\T3Toon\Domain\Model\EncodeOptions;
use RRP\T3Toon\Exception\ToonDecodeException;
use RRP\T3Toon\Service\ToonEncoder;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
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

    protected UsageLogger $usageLogger;

    protected ExtensionConfiguration $extensionConfiguration;

    public function __construct(
        ?UsageLogger $usageLogger = null,
        ?ExtensionConfiguration $extensionConfiguration = null,
    ) {
        $this->converter = GeneralUtility::makeInstance(ToonEncoder::class);
        $this->decoder = GeneralUtility::makeInstance(ToonDecoder::class);
        $this->extensionConfiguration = $extensionConfiguration
            ?? GeneralUtility::makeInstance(ExtensionConfiguration::class);
        // When DI is in play (autowired from Services.yaml), $usageLogger is injected.
        // Fallback path for `new Toon()` outside DI: build it explicitly so makeInstance
        // doesn't try (and fail) to construct UsageLogger from an empty container lookup.
        $this->usageLogger = $usageLogger ?? GeneralUtility::makeInstance(
            UsageLogger::class,
            GeneralUtility::makeInstance(ConnectionPool::class),
            $this->extensionConfiguration,
        );
    }

    /**
     * Convert arbitrary input into TOON format.
     *
     * When the "enabled" extension setting is off, returns the input as-is
     * (string verbatim, or JSON-encoded for arrays/objects) so callers can
     * disable optimization without code changes.
     *
     * @param mixed $input JSON, array, or object
     * @param EncodeOptions|null $options Optional encoding options; null = extension config
     * @return string TOON representation (or pass-through when disabled)
     */
    public function convert($input, ?EncodeOptions $options = null): string
    {
        $output = $this->isEnabled()
            ? $this->converter->toToon($input, $options)
            : $this->passthrough($input);
        $this->usageLogger->logEncoded($input, $output);
        return $output;
    }

    /**
     * Encode arbitrary input into TOON format (alias for convert).
     *
     * @param mixed $input JSON, array, or object
     * @param EncodeOptions|null $options Optional encoding options; null = extension config
     * @return string TOON representation (or pass-through when disabled)
     */
    public function encode($input, ?EncodeOptions $options = null): string
    {
        // Call the encoder directly (not $this->convert) to keep logging to one row per public call.
        $output = $this->isEnabled()
            ? $this->converter->toToon($input, $options)
            : $this->passthrough($input);
        $this->usageLogger->logEncoded($input, $output);
        return $output;
    }

    private function isEnabled(): bool
    {
        try {
            return (bool) $this->extensionConfiguration->get('rrp_t3toon', 'enabled');
        } catch (\Throwable) {
            return true;
        }
    }

    private function passthrough(mixed $input): string
    {
        if (is_string($input)) {
            return $input;
        }
        $json = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        return is_string($json) ? $json : '';
    }

    /**
     * Decode a TOON string into native PHP values.
     *
     * Objects decode to associative arrays, arrays to lists, and a bare scalar
     * or array at the document root is returned as-is.
     *
     * @param string $toon TOON-formatted string
     * @param DecodeOptions|null $options Optional decoding options; null = extension config
     * @return mixed Decoded value (associative array, list, scalar, or null)
     * @throws ToonDecodeException When the TOON input is malformed
     */
    public function decode(string $toon, ?DecodeOptions $options = null): mixed
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
     * @return mixed Decoded value (associative array, list, scalar, or null)
     * @throws ToonDecodeException When the TOON input is malformed
     */
    public static function decodeStatic(string $toon, ?DecodeOptions $options = null): mixed
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