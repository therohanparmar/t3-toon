<?php

declare(strict_types=1);

namespace RRP\T3Toon\Spec;

/**
 * Spec-compliant TOON encoder (JSON data model -> TOON text).
 *
 * Implements the TOON Specification v3.3 (https://github.com/toon-format/spec,
 * commit f55b93a). This class is intentionally free of any TYPO3 dependency so
 * it can be exercised directly by the language-agnostic conformance fixtures.
 *
 * Value model (JSON data model):
 *  - null / bool / int / float / string  -> primitives
 *  - list array (array_is_list)          -> JSON array
 *  - associative array or \stdClass      -> JSON object
 *  - empty array                         -> JSON array  (use \stdClass for {})
 */
final class Encoder
{
    public const DELIMITER_COMMA = ',';
    public const DELIMITER_TAB = "\t";
    public const DELIMITER_PIPE = '|';

    private string $indentUnit;

    /**
     * @param int $indent Spaces per indentation level (default 2; MUST be >= 1 per spec).
     * @param string $delimiter Document delimiter: comma (default), tab, or pipe.
     * @param string $keyFolding "off" (default) or "safe".
     * @param int|null $flattenDepth Max segments to fold when keyFolding is "safe"; null = unbounded.
     */
    public function __construct(
        private readonly int $indent = 2,
        private readonly string $delimiter = self::DELIMITER_COMMA,
        private readonly string $keyFolding = 'off',
        private readonly ?int $flattenDepth = null,
    ) {
        if ($indent < 1) {
            throw new \InvalidArgumentException('indent must be >= 1');
        }
        if (!in_array($delimiter, [self::DELIMITER_COMMA, self::DELIMITER_TAB, self::DELIMITER_PIPE], true)) {
            throw new \InvalidArgumentException('delimiter must be comma, tab, or pipe');
        }
        $this->indentUnit = str_repeat(' ', $indent);
    }

    /**
     * Encode a JSON-model value to a TOON document string.
     *
     * @param mixed $value
     */
    public function encode(mixed $value): string
    {
        $value = $this->normalize($value);
        $lines = [];

        switch ($this->classify($value)) {
            case 'object':
                $entries = $this->entries($value);
                if ($entries === []) {
                    return ''; // empty object at root => empty document (§8)
                }
                $this->emitObjectFields($entries, 0, $lines, true);
                break;
            case 'array':
                if ($value === []) {
                    $lines[] = '[]'; // empty root array (§9.1)
                } else {
                    $this->emitArray(null, $value, 0, $lines, true);
                }
                break;
            default:
                $lines[] = $this->encodeScalar($value);
        }

        return implode("\n", $lines);
    }

    // ------------------------------------------------------------------
    // Objects
    // ------------------------------------------------------------------

    /**
     * @param array<int, array{0: string, 1: mixed}> $entries
     * @param list<string> $lines
     */
    private function emitObjectFields(array $entries, int $depth, array &$lines, bool $allowFold): void
    {
        $siblingKeys = array_map(static fn($e) => (string) $e[0], $entries);

        foreach ($entries as [$key, $val]) {
            $key = (string) $key;

            if ($allowFold && $this->keyFolding === 'safe') {
                [$candidate, $candidateLeaf, $folded] = $this->foldChain($key, $val);
                if ($folded) {
                    // A folded (or partially folded) chain renders its remainder as plain
                    // nested objects: folding is disabled for the rest of this subtree (§13.4).
                    if (in_array($candidate, $siblingKeys, true)) {
                        // Collision avoidance: emit the chain unfolded instead.
                        $this->emitField($this->encodeKey($key), $val, $depth, $lines, false);
                    } else {
                        $this->emitField($this->encodeKey($candidate), $candidateLeaf, $depth, $lines, false);
                    }
                    continue;
                }
            }

            $this->emitField($this->encodeKey($key), $val, $depth, $lines, $allowFold);
        }
    }

    /**
     * Emit a single object field whose own line sits at $depth and whose children sit at $depth + 1.
     *
     * @param list<string> $lines
     */
    private function emitField(string $encodedKey, mixed $value, int $depth, array &$lines, bool $allowFold): void
    {
        $value = $this->normalize($value);
        $kind = $this->classify($value);

        if ($kind === 'array') {
            if ($value === []) {
                $lines[] = $this->pad($depth) . $encodedKey . ': []';
                return;
            }
            $this->emitArray($encodedKey, $value, $depth, $lines, $allowFold);
            return;
        }

        if ($kind === 'object') {
            $entries = $this->entries($value);
            if ($entries === []) {
                $lines[] = $this->pad($depth) . $encodedKey . ':';
                return;
            }
            $lines[] = $this->pad($depth) . $encodedKey . ':';
            $this->emitObjectFields($entries, $depth + 1, $lines, $allowFold);
            return;
        }

        // primitive
        $lines[] = $this->pad($depth) . $encodedKey . ': ' . $this->encodeScalar($value);
    }

    // ------------------------------------------------------------------
    // Arrays
    // ------------------------------------------------------------------

    /**
     * Emit a non-empty array. $encodedKey is null for the root array.
     *
     * @param list<string> $lines
     */
    private function emitArray(?string $encodedKey, array $arr, int $depth, array &$lines, bool $allowFold): void
    {
        $prefix = $encodedKey ?? '';
        $n = count($arr);

        if ($this->isTabular($arr)) {
            $fields = array_keys($this->entries($arr[0], true));
            $encodedFields = array_map(fn($f) => $this->encodeKey((string) $f), $fields);
            $lines[] = $this->pad($depth) . $prefix . $this->bracket($n)
                . '{' . implode($this->delimiter, $encodedFields) . '}:';
            foreach ($arr as $row) {
                $entries = $this->entries($row, true);
                $cells = [];
                foreach ($fields as $f) {
                    $cells[] = $this->encodeScalar($this->normalize($entries[$f] ?? null));
                }
                $lines[] = $this->pad($depth + 1) . implode($this->delimiter, $cells);
            }
            return;
        }

        if ($this->isAllPrimitive($arr)) {
            $vals = array_map(fn($v) => $this->encodeScalar($this->normalize($v)), $arr);
            $lines[] = $this->pad($depth) . $prefix . $this->bracket($n) . ': ' . implode($this->delimiter, $vals);
            return;
        }

        // Mixed / non-uniform: expanded list (§9.4).
        $lines[] = $this->pad($depth) . $prefix . $this->bracket($n) . ':';
        foreach ($arr as $item) {
            $this->emitListItem($this->normalize($item), $depth + 1, $lines, $allowFold);
        }
    }

    /**
     * @param list<string> $lines
     */
    private function emitListItem(mixed $item, int $depth, array &$lines, bool $allowFold): void
    {
        $kind = $this->classify($item);

        if ($kind === 'array') {
            if ($item === []) {
                $lines[] = $this->pad($depth) . '- ' . $this->bracket(0) . ':';
                return;
            }
            if ($this->isAllPrimitive($item)) {
                $vals = array_map(fn($v) => $this->encodeScalar($this->normalize($v)), $item);
                $lines[] = $this->pad($depth) . '- ' . $this->bracket(count($item)) . ': ' . implode($this->delimiter, $vals);
                return;
            }
            // Array of objects / nested arrays as a list item: header on hyphen line,
            // inner items at depth + 1. Tabular form is not available here (§9.4).
            $lines[] = $this->pad($depth) . '- ' . $this->bracket(count($item)) . ':';
            foreach ($item as $inner) {
                $this->emitListItem($this->normalize($inner), $depth + 1, $lines, $allowFold);
            }
            return;
        }

        if ($kind === 'object') {
            $entries = $this->entries($item);
            if ($entries === []) {
                $lines[] = $this->pad($depth) . '-'; // empty-object list item (§10)
                return;
            }
            // Emit fields one level deeper, then hoist the first line onto the hyphen.
            $sub = [];
            $this->emitObjectFields($entries, $depth + 1, $sub, $allowFold);
            $strip = $this->pad($depth + 1);
            $first = substr($sub[0], strlen($strip));
            $sub[0] = $this->pad($depth) . '- ' . $first;
            foreach ($sub as $line) {
                $lines[] = $line;
            }
            return;
        }

        // primitive
        $lines[] = $this->pad($depth) . '- ' . $this->encodeScalar($item);
    }

    // ------------------------------------------------------------------
    // Key folding (§13.4)
    // ------------------------------------------------------------------

    /**
     * @return array{0: string, 1: mixed, 2: bool} [foldedKey, leafValue, didFold]
     */
    private function foldChain(string $key, mixed $value): array
    {
        if (!$this->isIdentifierSegment($key)) {
            return [$key, $value, false];
        }

        $segments = [$key];
        $leaf = $this->normalize($value);
        $limit = $this->flattenDepth ?? PHP_INT_MAX;

        while (count($segments) < $limit) {
            if ($this->classify($leaf) !== 'object') {
                break;
            }
            $entries = $this->entries($leaf);
            if (count($entries) !== 1) {
                break; // not a single-key object -> chain stops
            }
            $childKey = (string) $entries[0][0];
            if (!$this->isIdentifierSegment($childKey)) {
                break;
            }
            $segments[] = $childKey;
            $leaf = $this->normalize($entries[0][1]);
        }

        if (count($segments) < 2) {
            return [$key, $value, false];
        }
        return [implode('.', $segments), $leaf, true];
    }

    private function isIdentifierSegment(string $s): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $s) === 1;
    }

    // ------------------------------------------------------------------
    // Scalars, keys, numbers (§2, §7)
    // ------------------------------------------------------------------

    private function encodeScalar(mixed $v): string
    {
        if ($v === null) {
            return 'null';
        }
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }
        if (is_int($v)) {
            return (string) $v;
        }
        if (is_float($v)) {
            return $this->formatNumber($v);
        }
        return $this->encodeString((string) $v);
    }

    private function encodeString(string $s): string
    {
        if ($this->mustQuote($s)) {
            return '"' . $this->escapeInner($s) . '"';
        }
        return $s;
    }

    private function encodeKey(string $k): string
    {
        // Unquoted keys allow dots (§7.3); folded keys land here as "a.b.c".
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $k) === 1) {
            return $k;
        }
        return '"' . $this->escapeInner($k) . '"';
    }

    private function mustQuote(string $s): bool
    {
        if ($s === '') {
            return true;
        }
        if (preg_match('/^\s|\s$/u', $s) === 1) {
            return true; // leading/trailing whitespace
        }
        if ($s === 'true' || $s === 'false' || $s === 'null') {
            return true;
        }
        if (preg_match('/^-?\d+(?:\.\d+)?(?:e[+-]?\d+)?$/i', $s) === 1) {
            return true; // numeric-like
        }
        if (preg_match('/[:"\\\\\[\]{}]/', $s) === 1) {
            return true; // colon, quote, backslash, brackets, braces
        }
        if (preg_match('/[\x00-\x1F]/', $s) === 1) {
            return true; // control characters
        }
        if (str_contains($s, $this->delimiter)) {
            return true; // active/document delimiter
        }
        if ($s === '-' || str_starts_with($s, '-')) {
            return true; // list-item marker ambiguity
        }
        return false;
    }

    private function escapeInner(string $s): string
    {
        $s = str_replace('\\', '\\\\', $s);
        $s = str_replace('"', '\\"', $s);
        $s = str_replace("\n", '\\n', $s);
        $s = str_replace("\r", '\\r', $s);
        $s = str_replace("\t", '\\t', $s);
        // Remaining C0 controls (excluding \n \r \t already handled) -> \uXXXX (lowercase).
        return preg_replace_callback(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F]/',
            static fn(array $m) => sprintf('\\u%04x', ord($m[0])),
            $s,
        );
    }

    /**
     * Canonical number formatting (§2). Integer-valued and in-range values render
     * as plain decimals without exponent; out-of-range values may use exponent form.
     */
    private function formatNumber(float $n): string
    {
        if (is_nan($n) || is_infinite($n)) {
            return 'null'; // defensive; normalization converts these to null already
        }
        if ($n == 0.0) {
            return '0'; // also normalizes -0.0
        }

        $neg = $n < 0;
        $abs = abs($n);
        $shortest = json_encode($abs); // serialize_precision = -1 -> shortest round-trip form

        // Decompose into digits + decimal point position.
        $exp = 0;
        $mant = $shortest;
        if (stripos($shortest, 'e') !== false) {
            [$mant, $e] = explode('e', strtolower($shortest));
            $exp = (int) $e;
        }
        $dot = strpos($mant, '.');
        if ($dot === false) {
            $intDigits = $mant;
            $fracDigits = '';
        } else {
            $intDigits = substr($mant, 0, $dot);
            $fracDigits = substr($mant, $dot + 1);
        }
        $digits = $intDigits . $fracDigits;
        $pointPos = strlen($intDigits) + $exp;

        $inRange = ($abs >= 1e-6 && $abs < 1e21);
        if ($inRange) {
            $plain = $this->buildPlainDecimal($digits, $pointPos);
            return ($neg ? '-' : '') . $plain;
        }

        // Out of canonical range: emit JSON exponent form with lowercase e and explicit sign.
        return ($neg ? '-' : '') . $this->buildExponent($digits, $pointPos);
    }

    private function buildPlainDecimal(string $digits, int $pointPos): string
    {
        if ($pointPos <= 0) {
            $out = '0.' . str_repeat('0', -$pointPos) . $digits;
        } elseif ($pointPos >= strlen($digits)) {
            $out = $digits . str_repeat('0', $pointPos - strlen($digits));
        } else {
            $out = substr($digits, 0, $pointPos) . '.' . substr($digits, $pointPos);
        }

        // Normalize: strip leading zeros (keep one), strip trailing fractional zeros.
        if (str_contains($out, '.')) {
            [$ip, $fp] = explode('.', $out, 2);
            $ip = ltrim($ip, '0');
            if ($ip === '') {
                $ip = '0';
            }
            $fp = rtrim($fp, '0');
            $out = $fp === '' ? $ip : ($ip . '.' . $fp);
        } else {
            $out = ltrim($out, '0');
            if ($out === '') {
                $out = '0';
            }
        }
        return $out;
    }

    private function buildExponent(string $digits, int $pointPos): string
    {
        $digits = ltrim($digits, '0');
        if ($digits === '') {
            return '0';
        }
        // Normalize to a single leading digit: d.dddd e EXP
        $lead = $digits[0];
        $rest = rtrim(substr($digits, 1), '0');
        $mant = $rest === '' ? $lead : ($lead . '.' . $rest);
        $e = $pointPos - 1;
        $sign = $e < 0 ? '-' : '+';
        return $mant . 'e' . $sign . abs($e);
    }

    private function bracket(int $n): string
    {
        $sym = $this->delimiter === self::DELIMITER_COMMA ? '' : $this->delimiter;
        return '[' . $n . $sym . ']';
    }

    private function pad(int $depth): string
    {
        return str_repeat($this->indentUnit, $depth);
    }

    // ------------------------------------------------------------------
    // Value-model helpers
    // ------------------------------------------------------------------

    private function normalize(mixed $v): mixed
    {
        if (is_float($v) && (is_nan($v) || is_infinite($v))) {
            return null; // §3 NaN/Inf -> null
        }
        return $v;
    }

    private function classify(mixed $v): string
    {
        if ($v === null) {
            return 'null';
        }
        if (is_object($v)) {
            return 'object';
        }
        if (is_array($v)) {
            return array_is_list($v) ? 'array' : 'object';
        }
        return 'primitive';
    }

    /**
     * Ordered list of [key, value] pairs for an object value.
     *
     * @param bool $assoc When true, return an associative key=>value map instead.
     * @return array<int|string, mixed>
     */
    private function entries(mixed $v, bool $assoc = false): array
    {
        $map = is_object($v) ? get_object_vars($v) : $v;
        if ($assoc) {
            return $map;
        }
        $out = [];
        foreach ($map as $k => $val) {
            $out[] = [(string) $k, $val];
        }
        return $out;
    }

    private function isAllPrimitive(array $arr): bool
    {
        foreach ($arr as $v) {
            $v = $this->normalize($v);
            if ($v !== null && !is_scalar($v)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Tabular when every element is a non-empty object, all share the same key set,
     * and all values are primitives (§9.3).
     */
    private function isTabular(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }
        $refKeys = null;
        foreach ($arr as $el) {
            if ($this->classify($el) !== 'object') {
                return false;
            }
            $map = $this->entries($el, true);
            if ($map === []) {
                return false; // empty object forbids tabular form
            }
            foreach ($map as $val) {
                $val = $this->normalize($val);
                if ($val !== null && !is_scalar($val)) {
                    return false; // nested value
                }
            }
            $keys = array_map('strval', array_keys($map));
            sort($keys);
            if ($refKeys === null) {
                $refKeys = $keys;
            } elseif ($keys !== $refKeys) {
                return false;
            }
        }
        return true;
    }
}
