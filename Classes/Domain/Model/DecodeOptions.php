<?php

declare(strict_types=1);

namespace RRP\T3Toon\Domain\Model;

/**
 * Optional settings for decoding TOON to PHP.
 * Null properties fall back to extension configuration.
 */
final class DecodeOptions
{
    public function __construct(
        /** Coerce "true"/"false"/"null"/numeric strings to PHP types (default from config: true). */
        public readonly ?bool $coerceScalarTypes = null,
    ) {
    }

    /**
     * Overrides to merge with extension config. Only non-null keys are included.
     *
     * @return array<string, mixed>
     */
    public function toConfigOverrides(): array
    {
        $overrides = [];
        if ($this->coerceScalarTypes !== null) {
            $overrides['coerce_scalar_types'] = $this->coerceScalarTypes;
        }
        return $overrides;
    }

    public static function default(): self
    {
        return new self();
    }

    /** Lenient: do not coerce scalar types (keep as strings). */
    public static function lenient(): self
    {
        return new self(coerceScalarTypes: false);
    }
}
