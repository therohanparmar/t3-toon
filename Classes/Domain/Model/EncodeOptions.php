<?php

declare(strict_types=1);

namespace RRP\T3Toon\Domain\Model;

/**
 * Optional settings for encoding to spec-compliant TOON.
 * Null properties fall back to extension configuration.
 *
 * @see \RRP\T3Toon\Spec\Encoder
 */
final class EncodeOptions
{
    public const DELIMITER_COMMA = ',';
    public const DELIMITER_TAB = "\t";
    public const DELIMITER_PIPE = '|';

    public const KEY_FOLDING_OFF = 'off';
    public const KEY_FOLDING_SAFE = 'safe';

    public function __construct(
        /** Spaces per indentation level (spec default 2; MUST be >= 1). */
        public readonly ?int $indent = null,
        /** Document delimiter: comma (default), tab, or pipe. */
        public readonly ?string $delimiter = null,
        /** Key folding mode: "off" (default) or "safe". */
        public readonly ?string $keyFolding = null,
        /** Max segments to fold when keyFolding is "safe"; null = unbounded. */
        public readonly ?int $flattenDepth = null,
    ) {
        if ($this->indent !== null && $this->indent < 1) {
            throw new \InvalidArgumentException('indent must be >= 1');
        }
        if ($this->delimiter !== null
            && !in_array($this->delimiter, [self::DELIMITER_COMMA, self::DELIMITER_TAB, self::DELIMITER_PIPE], true)
        ) {
            throw new \InvalidArgumentException('delimiter must be comma, tab, or pipe');
        }
        if ($this->keyFolding !== null
            && !in_array($this->keyFolding, [self::KEY_FOLDING_OFF, self::KEY_FOLDING_SAFE], true)
        ) {
            throw new \InvalidArgumentException('keyFolding must be "off" or "safe"');
        }
        if ($this->flattenDepth !== null && $this->flattenDepth < 0) {
            throw new \InvalidArgumentException('flattenDepth must be non-negative');
        }
    }

    /**
     * Overrides to merge with extension config. Only non-null keys are included.
     *
     * @return array<string, mixed>
     */
    public function toConfigOverrides(): array
    {
        $overrides = [];
        if ($this->indent !== null) {
            $overrides['indent'] = $this->indent;
        }
        if ($this->delimiter !== null) {
            $overrides['delimiter'] = $this->delimiter;
        }
        if ($this->keyFolding !== null) {
            $overrides['key_folding'] = $this->keyFolding;
        }
        if ($this->flattenDepth !== null) {
            $overrides['flatten_depth'] = $this->flattenDepth;
        }
        return $overrides;
    }

    public static function default(): self
    {
        return new self();
    }

    /** Canonical compact output (indent 2, comma delimiter). */
    public static function compact(): self
    {
        return new self(indent: 2, delimiter: self::DELIMITER_COMMA);
    }

    /** More readable output: indent 4. */
    public static function readable(): self
    {
        return new self(indent: 4);
    }

    /** Tab delimiter for spreadsheet-friendly output. */
    public static function tabular(): self
    {
        return new self(delimiter: self::DELIMITER_TAB);
    }

    /** Enable safe key folding (collapse single-key object chains into dotted paths). */
    public static function folded(): self
    {
        return new self(keyFolding: self::KEY_FOLDING_SAFE);
    }
}
