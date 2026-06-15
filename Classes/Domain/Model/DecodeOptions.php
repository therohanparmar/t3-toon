<?php

declare(strict_types=1);

namespace RRP\T3Toon\Domain\Model;

/**
 * Optional settings for decoding spec-compliant TOON.
 * Null properties fall back to extension configuration.
 *
 * @see \RRP\T3Toon\Spec\Decoder
 */
final class DecodeOptions
{
    public const EXPAND_PATHS_OFF = 'off';
    public const EXPAND_PATHS_SAFE = 'safe';

    public function __construct(
        /** Enforce strict-mode validation (counts, indentation, escapes); default true. */
        public readonly ?bool $strict = null,
        /** Dotted-key path expansion: "off" (default) or "safe". */
        public readonly ?string $expandPaths = null,
    ) {
        if ($this->expandPaths !== null
            && !in_array($this->expandPaths, [self::EXPAND_PATHS_OFF, self::EXPAND_PATHS_SAFE], true)
        ) {
            throw new \InvalidArgumentException('expandPaths must be "off" or "safe"');
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
        if ($this->strict !== null) {
            $overrides['strict'] = $this->strict;
        }
        if ($this->expandPaths !== null) {
            $overrides['expand_paths'] = $this->expandPaths;
        }
        return $overrides;
    }

    public static function default(): self
    {
        return new self();
    }

    /** Lenient parsing: relax strict-mode validation (non-multiple indentation, counts, blank lines). */
    public static function lenient(): self
    {
        return new self(strict: false);
    }

    /** Expand dotted keys into nested objects (safe mode). */
    public static function expanded(): self
    {
        return new self(expandPaths: self::EXPAND_PATHS_SAFE);
    }
}
