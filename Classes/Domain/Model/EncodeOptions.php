<?php

declare(strict_types=1);

namespace RRP\T3Toon\Domain\Model;

/**
 * Optional settings for encoding PHP data to TOON.
 * Null properties fall back to extension configuration.
 */
final class EncodeOptions
{
    public const DELIMITER_COMMA = ',';
    public const DELIMITER_TAB = "\t";

    public function __construct(
        /** Number of spaces per indentation level (default from config: 2). */
        public readonly ?int $indent = null,
        /** Field delimiter for tabular/arrays: comma or tab (default comma). */
        public readonly ?string $delimiter = null,
        /** Max rows to emit in tabular blocks (default from config). */
        public readonly ?int $maxPreviewItems = null,
        /** Escape style: 'backslash' (default from config). */
        public readonly ?string $escapeStyle = null,
        /** Min rows to render as tabular (default from config: 2). */
        public readonly ?int $minRowsToTabular = null,
        /** Emit primitive arrays as single line [N]: v1,v2,v3 (spec-style). Default false. */
        public readonly ?bool $primitiveArrayHeader = null,
    ) {
        if ($this->indent !== null && $this->indent < 0) {
            throw new \InvalidArgumentException('indent must be non-negative');
        }
        if ($this->delimiter !== null && $this->delimiter !== self::DELIMITER_COMMA && $this->delimiter !== self::DELIMITER_TAB) {
            throw new \InvalidArgumentException('delimiter must be comma or tab');
        }
        if ($this->maxPreviewItems !== null && $this->maxPreviewItems < 0) {
            throw new \InvalidArgumentException('maxPreviewItems must be non-negative');
        }
        if ($this->minRowsToTabular !== null && $this->minRowsToTabular < 0) {
            throw new \InvalidArgumentException('minRowsToTabular must be non-negative');
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
        if ($this->maxPreviewItems !== null) {
            $overrides['max_preview_items'] = $this->maxPreviewItems;
        }
        if ($this->escapeStyle !== null) {
            $overrides['escape_style'] = $this->escapeStyle;
        }
        if ($this->minRowsToTabular !== null) {
            $overrides['min_rows_to_tabular'] = $this->minRowsToTabular;
        }
        if ($this->primitiveArrayHeader !== null) {
            $overrides['primitive_array_header'] = $this->primitiveArrayHeader;
        }
        return $overrides;
    }

    public static function default(): self
    {
        return new self();
    }

    /** Compact output: indent 0, comma delimiter. */
    public static function compact(): self
    {
        return new self(indent: 0, delimiter: self::DELIMITER_COMMA);
    }

    /** Readable output: indent 4. */
    public static function readable(): self
    {
        return new self(indent: 4);
    }

    /** Tab delimiter for spreadsheet-friendly output. */
    public static function tabular(): self
    {
        return new self(delimiter: self::DELIMITER_TAB);
    }
}
