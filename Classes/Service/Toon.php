<?php

declare(strict_types=1);

namespace RRP\T3Toon\Service;

use RRP\T3Toon\Service\ToonEncoder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @return string TOON representation
     */
    public function convert($input): string
    {
        return $this->converter->toToon($input);
    }

    /**
     * Encode arbitrary input into TOON format.
     *
     * Alias for `convert()` method.
     *
     * @param mixed $input JSON, array, or object
     * @return string TOON representation
     */
    public function encode($input): string
    {
        return $this->convert($input);
    }

    /**
     * Decode a TOON string into an associative PHP array.
     *
     * Handles nested blocks, tabular structures, escaped values,
     * and scalar coercion (e.g., "true" → true).
     *
     * @param string $toon
     * @return array
     */
    public function decode(string $toon): array
    {
        return $this->decoder->fromToon($toon);
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