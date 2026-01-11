<?php

declare(strict_types=1);

namespace RRP\T3Toon\Service;

use RRP\T3Toon\Utility\ToonHelper;

class ToonEncoder
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = ToonHelper::getConfig();
    }

    /**
     * Converts various input types into TOON format.
     *
     * Supports arrays, objects, JSON strings, and scalars.
     *
     * @param mixed $input Input data (array, object, JSON string, scalar)
     * @return string TOON-formatted string
     */
    public function toToon(mixed $input): string
    {
        // ------------------------------------------------------------------
        // Case 1: JSON string — attempt decoding first.
        // ------------------------------------------------------------------
        // Automatically detects JSON-like strings and converts them into arrays
        // before proceeding. This allows flexible CLI and API inputs.
        if (is_string($input) && $this->looksLikeJson($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->valueToToon($decoded);
            }
        }

        // ------------------------------------------------------------------
        // Case 2: Convert objects to associative arrays recursively.
        // ------------------------------------------------------------------
        // Ensures that any stdClass or custom DTOs are handled uniformly.
        if (is_object($input)) {
            $input = json_decode(json_encode($input), true);
        }

        // ------------------------------------------------------------------
        // Case 3: Arrays or iterable structures — recursively convert.
        // ------------------------------------------------------------------
        if (is_array($input) || $input instanceof \Traversable) {
            return $this->valueToToon((array) $input);
        }

        // ------------------------------------------------------------------
        // Case 4: Fallback for scalars (numbers, strings, bool, null).
        // ------------------------------------------------------------------
        return $this->textToToon((string) $input);
    }

    /**
     * Recursive conversion logic for arrays and scalars.
     *
     * Handles nested structures, indentation depth, and type-specific rendering.
     *
     * @param mixed $value The value to convert (array, scalar, nested).
     * @param int   $depth Current indentation level.
     * @return string Formatted TOON representation.
     */
    protected function valueToToon(mixed $value, int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth);

        // ------------------------------------------------------------------
        // Handle arrays
        // ------------------------------------------------------------------
        if (is_array($value)) {
            // Sequential arrays (indexed numerically)
            if ($this->isSequentialArray($value)) {
                // If it’s a list of objects with identical keys, render as a table.
                if ($this->isArrayOfUniformObjects($value)) {
                    return $this->arrayOfObjectsToToon($value, $depth);
                }

                // Otherwise, render line by line (scalar or nested).
                $lines = [];
                foreach ($value as $item) {
                    if ($this->isScalar($item)) {
                        // Inline scalar value (simple types)
                        $lines[] = $indent . $this->inlineScalar($item);
                    } else {
                        // Recursively handle nested arrays or objects
                        $lines[] = $this->valueToToon($item, $depth + 1);
                    }
                }
                return implode("\n", $lines);
            }

            // ------------------------------------------------------------------
            // Associative arrays (key-value objects)
            // ------------------------------------------------------------------
            // Preserve key order exactly as given (no sorting).
            $lines = [];
            foreach ($value as $key => $val) {
                $safeKey = $this->safeKey((string) $key);

                if ($this->isScalar($val)) {
                    $lines[] = $indent . "{$safeKey}: " . $this->inlineScalar($val);
                } else {
                    // Print key followed by nested structure
                    $lines[] = $indent . "{$safeKey}:";
                    $lines[] = $this->valueToToon($val, $depth + 1);
                }
            }
            return implode("\n", $lines);
        }

        // ------------------------------------------------------------------
        // Handle scalar values directly
        // ------------------------------------------------------------------
        return $indent . $this->inlineScalar($value);
    }

    /**
     * Converts an array of associative arrays (objects) into TOON tabular format.
     *
     * @param array $arr   Array of associative arrays
     * @param int   $depth Current indentation level
     * @return string TOON-formatted table representation
     */
    protected function arrayOfObjectsToToon(array $arr, int $depth = 0): string
    {
        if (empty($arr)) {
            // Edge case: empty list
            return str_repeat('  ', $depth) . 'items[0]{}:';
        }

        // Preserve the key order from the first row (no sorting)
        $first = (array) $arr[0];
        $fields = array_keys($first);
        $indent = str_repeat('  ', $depth);

        $header = $indent . 'items[' . count($arr) . ']{' . implode(',', $fields) . '}:';
        $rows = [];
        $max = min(count($arr), (int) $this->config['max_preview_items']);

        for ($i = 0; $i < $max; $i++) {
            $row = [];
            foreach ($fields as $f) {
                // Safely format each scalar field
                $row[] = $this->inlineScalar($arr[$i][$f] ?? null);
            }
            $rows[] = $indent . '  ' . implode(',', $row);
        }

        return $header . "\n" . implode("\n", $rows);
    }

    /**
     * Safely escape scalar values for use within TOON syntax.
     *
     * Handles commas, colons, slashes, and newline normalization.
     *
     * @param mixed $v Scalar value
     * @return string Escaped and formatted representation
     */
    protected function inlineScalar(mixed $v): string
    {
        if ($v === null) {
            return '';
        }

        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }

        if (is_int($v) || is_float($v)) {
            return (string) $v;
        }

        if (is_array($v)) {
            $parts = [];
            foreach ($v as $key => $val) {
                $parts[] = $key . ':' . $this->inlineScalar($val);
            }
            return implode(',', $parts);
        }

        // Normalize whitespace and line breaks
        $s = trim(preg_replace('/\s+/', ' ', (string) $v));

        if ($this->config['escape_style'] === 'backslash') {
            // Backslash escaping style for special characters
            $s = str_replace('\\', '\\\\', $s);
            $s = str_replace(',', '\\,', $s);
            $s = str_replace(':', '\\:', $s);
            $s = str_replace("\n", '\\n', $s);
            return $s;
        }

        // Default fallback escape
        return str_replace("\n", '\\n', $s);
    }

    /**
     * Converts plain text into TOON format.
     *
     * @param string $text Input text
     * @return string TOON-formatted text
     */
    protected function textToToon(string $text): string
    {
        return $this->inlineScalar($text);
    }

    /**
     * Sanitize a key name for TOON output.
     * Removes disallowed characters and enforces lowercase for consistency.
     *
     * @param string $k Raw key name
     * @return string Safe key
     */
    protected function safeKey(string $k): string
    {
        $key = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $k);
        return strtolower($key);
    }

    // ----------------------------------------------------------------------
    // Utility Helpers
    // ----------------------------------------------------------------------

    /** Check if a value is scalar or null. */
    protected function isScalar(mixed $v): bool
    {
        return is_null($v) || is_scalar($v);
    }

    /** Detects whether a given string is likely valid JSON. */
    protected function looksLikeJson(string $s): bool
    {
        $s = trim($s);
        return $s !== '' && (str_starts_with($s, '{') || str_starts_with($s, '['));
    }

    /** Determine if an array is sequential (non-associative). */
    protected function isSequentialArray(array $arr): bool
    {
        return array_values($arr) === $arr;
    }

    /**
     * Determines whether the array is a uniform list of associative arrays
     * (i.e., same keys in all rows) — suitable for TOON tabular rendering.
     *
     * @param array $arr Input array
     * @return bool True if uniform
     */
    protected function isArrayOfUniformObjects(array $arr): bool
    {
        $min = (int) $this->config['min_rows_to_tabular'];
        if (count($arr) < $min) {
            return false;
        }

        $firstKeys = null;
        foreach ($arr as $item) {
            if (!is_array($item)) {
                return false;
            }

            // Maintain original key order
            $keys = array_keys($item);
            if ($firstKeys === null) {
                $firstKeys = $keys;
            } elseif ($keys !== $firstKeys) {
                return false;
            }
        }

        return true;
    }
}
