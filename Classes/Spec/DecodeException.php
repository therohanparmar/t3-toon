<?php

declare(strict_types=1);

namespace RRP\T3Toon\Spec;

/**
 * Raised by the spec Decoder on malformed TOON or strict-mode violations.
 *
 * Dependency-free (extends \RuntimeException) so the spec engine can be used
 * outside TYPO3. The TYPO3 service layer maps this to ToonDecodeException.
 */
final class DecodeException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $lineNumber = null,
        public readonly ?string $snippet = null,
    ) {
        $suffix = $lineNumber !== null ? sprintf(' (line %d)', $lineNumber) : '';
        parent::__construct($message . $suffix);
    }
}
