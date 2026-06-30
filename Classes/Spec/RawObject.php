<?php

declare(strict_types=1);

namespace RRP\T3Toon\Spec;

/**
 * Intermediate object node used during decoding.
 *
 * Keeps insertion order and the quoted/unquoted provenance of each key so that
 * §14.4 duplicate-key checks run on the pre-expansion structure and §13.4 path
 * expansion can skip quoted keys. Converted to \stdClass by the Decoder when
 * finalizing the result.
 */
final class RawObject
{
    /** @var list<array{k: string, q: bool, v: mixed}> */
    public array $entries = [];

    /**
     * Add (or, under last-write-wins, replace) a field.
     *
     * @throws DecodeException on duplicate keys when $strict is true (§14.4)
     */
    public function add(string $key, bool $quoted, mixed $value, bool $strict, ?int $lineNumber = null): void
    {
        foreach ($this->entries as $i => $entry) {
            if ($entry['k'] === $key) {
                if ($strict) {
                    throw new DecodeException(sprintf('Duplicate sibling key "%s"', $key), $lineNumber, $key);
                }
                // Last-write-wins: overwrite value, keep original position (§14.4).
                $this->entries[$i]['v'] = $value;
                $this->entries[$i]['q'] = $quoted;
                return;
            }
        }
        $this->entries[] = ['k' => $key, 'q' => $quoted, 'v' => $value];
    }
}
