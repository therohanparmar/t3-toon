<?php

declare(strict_types=1);

namespace RRP\T3Toon\Exception;

/**
 * Thrown when TOON input cannot be decoded (malformed line, invalid structure).
 */
class ToonDecodeException extends ToonException
{
    public function __construct(
        string $message,
        protected readonly int $lineNumber = 0,
        protected readonly ?string $snippet = null,
        ?\Throwable $previous = null,
    ) {
        $fullMessage = $message;
        if ($this->lineNumber > 0) {
            $fullMessage = "Line {$this->lineNumber}: {$message}";
        }
        if ($this->snippet !== null && $this->snippet !== '') {
            $fullMessage .= "\n  > " . $this->snippet;
        }
        parent::__construct($fullMessage, 0, $previous);
    }

    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    public function getSnippet(): ?string
    {
        return $this->snippet;
    }
}
