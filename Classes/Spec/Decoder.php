<?php

declare(strict_types=1);

namespace RRP\T3Toon\Spec;

/**
 * Spec-compliant TOON decoder (TOON text -> JSON data model).
 *
 * Implements the TOON Specification v3.3. Free of any TYPO3 dependency so it
 * can be exercised directly by the language-agnostic conformance fixtures.
 *
 * Objects are returned as \stdClass (so empty objects serialize as {} and key
 * order is preserved); arrays as PHP lists; primitives as null/bool/int/float/string.
 */
final class Decoder
{
    private const UNQUOTED_KEY = '/^[A-Za-z_][A-Za-z0-9_.]*$/';
    private const IDENTIFIER_SEG = '/^[A-Za-z_][A-Za-z0-9_]*$/';
    private const NUMBER = '/^-?(?:0|[1-9]\d*)(?:\.\d+)?(?:[eE][+-]?\d+)?$/';

    /** @var list<array{blank: bool, sp: int, tab: bool, content: string, no: int}> */
    private array $lines = [];
    private int $pos = 0;

    public function __construct(
        private readonly int $indent = 2,
        private readonly bool $strict = true,
        private readonly string $expandPaths = 'off',
    ) {
        if ($indent < 1) {
            throw new \InvalidArgumentException('indent must be >= 1');
        }
    }

    public function decode(string $toon): mixed
    {
        $this->lines = $this->preprocess($toon);
        $this->pos = 0;

        $raw = $this->decodeRoot();
        return $this->finalize($raw);
    }

    // ------------------------------------------------------------------
    // Line preprocessing
    // ------------------------------------------------------------------

    /** @return list<array{blank: bool, sp: int, tab: bool, content: string, no: int}> */
    private function preprocess(string $toon): array
    {
        $rawLines = preg_split("/\r\n|\r|\n/", $toon);
        $out = [];
        foreach ($rawLines as $i => $raw) {
            $blank = trim($raw) === '';
            $wsLen = strlen($raw) - strlen(ltrim($raw, " \t"));
            $ws = substr($raw, 0, $wsLen);
            $out[] = [
                'blank' => $blank,
                'sp' => strlen($raw) - strlen(ltrim($raw, ' ')), // leading spaces only
                'tab' => str_contains($ws, "\t"),
                'content' => ltrim($raw, " \t"),
                'no' => $i + 1,
            ];
        }
        return $out;
    }

    private function depthOf(array $line): int
    {
        if (!$line['blank'] && $line['tab']) {
            if ($this->strict) {
                throw new DecodeException('Tab character used in indentation', $line['no']);
            }
        }
        $sp = $line['sp'];
        if ($this->strict) {
            if ($sp % $this->indent !== 0) {
                throw new DecodeException(
                    sprintf('Indentation of %d spaces is not a multiple of %d', $sp, $this->indent),
                    $line['no'],
                );
            }
            return intdiv($sp, $this->indent);
        }
        return intdiv($sp, $this->indent);
    }

    /** Index of next non-blank line at or after $this->pos, or null. */
    private function peekNonBlank(): ?int
    {
        $i = $this->pos;
        while ($i < count($this->lines) && $this->lines[$i]['blank']) {
            $i++;
        }
        return $i < count($this->lines) ? $i : null;
    }

    // ------------------------------------------------------------------
    // Root form (§5)
    // ------------------------------------------------------------------

    private function decodeRoot(): mixed
    {
        // Collect non-blank lines for root-form detection.
        $nonBlank = [];
        foreach ($this->lines as $idx => $line) {
            if (!$line['blank']) {
                $nonBlank[] = $idx;
            }
        }

        if ($nonBlank === []) {
            return new RawObject(); // empty document -> empty object
        }

        $first = $this->lines[$nonBlank[0]];
        $firstContent = $first['content'];

        // Root array header (no key): starts with '['.
        if (str_starts_with($firstContent, '[')) {
            $hdr = $this->tryHeader($firstContent);
            if ($hdr !== null && $hdr['keyKind'] === 'none') {
                $this->pos = $nonBlank[0];
                $this->pos++; // consume header line
                return $this->parseArrayValue($hdr, $this->depthOf($first) + 1);
            }
            if ($firstContent === '[]' && count($nonBlank) === 1) {
                return [];
            }
        }

        // Single non-blank line that is neither array header nor key-value -> single primitive.
        if (count($nonBlank) === 1) {
            if ($firstContent === '[]') {
                return [];
            }
            if (!$this->looksLikeField($firstContent)) {
                return $this->parsePrimitiveToken($firstContent);
            }
        }

        // Otherwise: object at depth 0.
        $this->pos = $nonBlank[0];
        return $this->parseObject(0);
    }

    /** True if the line looks like an object field (has a header or an unquoted colon). */
    private function looksLikeField(string $content): bool
    {
        if ($this->tryHeader($content) !== null) {
            return true;
        }
        return $this->firstUnquotedColon($content) !== -1 || $this->hasHeaderAttempt($content);
    }

    // ------------------------------------------------------------------
    // Objects
    // ------------------------------------------------------------------

    private function parseObject(int $depth): RawObject
    {
        $obj = new RawObject();

        while (true) {
            // Skip ignorable blank lines between object fields.
            while ($this->pos < count($this->lines) && $this->lines[$this->pos]['blank']) {
                $this->pos++;
            }
            if ($this->pos >= count($this->lines)) {
                break;
            }
            $line = $this->lines[$this->pos];
            $d = $this->depthOf($line);
            if ($d < $depth) {
                break; // dedent: belongs to a parent
            }
            if ($d > $depth) {
                throw new DecodeException('Unexpected indentation', $line['no'], $line['content']);
            }
            $content = $line['content'];
            $this->pos++; // consume field line
            $this->parseFieldInto($obj, $content, $depth + 1, $line['no']);
        }

        return $obj;
    }

    /**
     * Parse one object field from $content. The field's own line has already been consumed;
     * nested children (object fields, list items, tabular rows) live at $childDepth.
     */
    private function parseFieldInto(RawObject $obj, string $content, int $childDepth, int $lineNo): void
    {
        $hdr = $this->tryHeader($content);
        if ($hdr !== null && $hdr['keyKind'] === 'present') {
            $value = $this->parseArrayValue($hdr, $childDepth);
            $obj->add($hdr['key'], $hdr['keyQuoted'], $value, $this->strict, $lineNo);
            return;
        }

        // Invalid array-header attempt (e.g. foo[1][bar]:, foo[bar]:, items[03]:, items[2] :).
        if ($this->hasHeaderAttempt($content)) {
            if ($this->strict) {
                throw new DecodeException('Malformed array header', $lineNo, $content);
            }
            // Non-strict fall-through: treat as a literal key-value line (§6).
            $cp = $this->firstUnquotedColon($content);
            if ($cp === -1) {
                throw new DecodeException('Missing colon in key context', $lineNo, $content);
            }
            $key = substr($content, 0, $cp);
            $rest = substr($content, $cp + 1);
            $obj->add($key, false, $this->parseScalarOrEmpty($rest, $childDepth), $this->strict, $lineNo);
            return;
        }

        // Plain key: value (or nested/empty object, or empty array).
        [$key, $quoted, $rest, $found] = $this->splitKeyColon($content);
        if (!$found) {
            throw new DecodeException('Missing colon in key context', $lineNo, $content);
        }
        $obj->add($key, $quoted, $this->parseScalarOrEmpty($rest, $childDepth), $this->strict, $lineNo);
    }

    /** Resolve the post-colon remainder to a value: nested/empty object, empty array, or scalar. */
    private function parseScalarOrEmpty(string $rest, int $childDepth): mixed
    {
        $restTrim = trim($rest);
        if ($restTrim === '') {
            return $this->parseObject($childDepth); // nested or empty object (§8)
        }
        if ($restTrim === '[]') {
            return []; // canonical empty array (§9.1)
        }
        return $this->parseScalarValue($rest);
    }

    // ------------------------------------------------------------------
    // Arrays
    // ------------------------------------------------------------------

    /**
     * Parse the value of an array header. Rows / list items / tabular rows live at $childDepth.
     *
     * @param array{keyKind: string, key: string, keyQuoted: bool, n: int, delim: string, fields: ?list<array{name:string,quoted:bool}>, rest: string} $hdr
     * @return list<mixed>
     */
    private function parseArrayValue(array $hdr, int $childDepth): array
    {
        $n = $hdr['n'];
        $delim = $hdr['delim'];

        if ($hdr['fields'] !== null) {
            return $this->parseTabularRows($childDepth, $n, $hdr['fields'], $delim);
        }

        $restTrim = trim($hdr['rest']);
        if ($restTrim !== '') {
            return $this->parseInlineArray($hdr['rest'], $n, $delim, $hdr['lineNo'] ?? null);
        }

        if ($n === 0) {
            return [];
        }

        return $this->parseListItems($childDepth, $n, $delim);
    }

    /** @return list<mixed> */
    private function parseInlineArray(string $rest, int $n, string $delim, ?int $lineNo): array
    {
        $tokens = $this->splitDelimited($rest, $delim);
        $values = array_map(fn($t) => $this->parsePrimitiveToken($t), $tokens);
        if ($this->strict && count($values) !== $n) {
            throw new DecodeException(
                sprintf('Inline array length mismatch: expected %d, got %d', $n, count($values)),
                $lineNo,
            );
        }
        return $values;
    }

    /** @return list<mixed> */
    private function parseListItems(int $childDepth, int $n, string $delim): array
    {
        $items = [];
        while (count($items) < $n) {
            if (!$this->advanceToArrayElement($childDepth)) {
                break;
            }
            $line = $this->lines[$this->pos];
            if ($this->depthOf($line) !== $childDepth) {
                break;
            }
            $content = $line['content'];
            if ($content !== '-' && !str_starts_with($content, '- ')) {
                break;
            }
            $this->pos++; // consume hyphen line
            $items[] = $this->parseListItemBody($content, $childDepth, $delim);
        }
        if ($this->strict && (count($items) !== $n || $this->hasMoreElement($childDepth, false, $delim))) {
            throw new DecodeException(sprintf('List array length mismatch: expected %d, got %d', $n, count($items)));
        }
        return $items;
    }

    private function parseListItemBody(string $content, int $itemDepth, string $parentDelim): mixed
    {
        if ($content === '-') {
            return new RawObject(); // empty-object list item (§10)
        }
        $r = substr($content, 2); // strip "- "

        // Direct array list item: "- [M…]: …"
        $hdr = $this->tryHeader($r);
        if ($hdr !== null && $hdr['keyKind'] === 'none') {
            return $this->parseArrayValue($hdr, $itemDepth + 1);
        }

        // Object list item: first field on the hyphen line (children at itemDepth + 2).
        if (($hdr !== null && $hdr['keyKind'] === 'present')
            || $this->firstUnquotedColon($r) !== -1
            || $this->hasHeaderAttempt($r)
        ) {
            $obj = new RawObject();
            $this->parseFieldInto($obj, $r, $itemDepth + 2, $this->lines[$this->pos - 1]['no']);
            // Sibling fields at itemDepth + 1.
            while (true) {
                while ($this->pos < count($this->lines) && $this->lines[$this->pos]['blank']) {
                    $this->pos++;
                }
                if ($this->pos >= count($this->lines)) {
                    break;
                }
                $line = $this->lines[$this->pos];
                if ($this->depthOf($line) !== $itemDepth + 1) {
                    break;
                }
                $this->pos++;
                $this->parseFieldInto($obj, $line['content'], $itemDepth + 2, $line['no']);
            }
            return $obj;
        }

        // Primitive list item.
        return $this->parsePrimitiveToken($r);
    }

    /**
     * @param list<array{name:string,quoted:bool}> $fields
     * @return list<mixed>
     */
    private function parseTabularRows(int $childDepth, int $n, array $fields, string $delim): array
    {
        $rows = [];
        $fieldCount = count($fields);
        while (count($rows) < $n) {
            if (!$this->advanceToArrayElement($childDepth)) {
                break;
            }
            $line = $this->lines[$this->pos];
            if ($this->depthOf($line) !== $childDepth) {
                break;
            }
            $content = $line['content'];
            if ($this->isKeyValueLine($content, $delim)) {
                break; // colon-before-delimiter terminates rows (§9.3)
            }
            $this->pos++;
            $cells = $this->splitDelimited($content, $delim);
            if ($this->strict && count($cells) !== $fieldCount) {
                throw new DecodeException(
                    sprintf('Tabular row width mismatch: expected %d, got %d', $fieldCount, count($cells)),
                    $line['no'],
                    $content,
                );
            }
            $row = new RawObject();
            foreach ($fields as $i => $field) {
                $row->add($field['name'], $field['quoted'], $this->parsePrimitiveToken($cells[$i] ?? ''), $this->strict, $line['no']);
            }
            $rows[] = $row;
        }
        if ($this->strict && (count($rows) !== $n || $this->hasMoreElement($childDepth, true, $delim))) {
            throw new DecodeException(sprintf('Tabular row count mismatch: expected %d, got %d', $n, count($rows)));
        }
        return $rows;
    }

    /**
     * Peek (without consuming) whether another array element exists at $childDepth —
     * used to detect "too many" rows/items. Blank lines are skipped, not errored, so
     * a blank line legitimately following the array's end is not misreported.
     */
    private function hasMoreElement(int $childDepth, bool $tabular, string $delim): bool
    {
        $idx = $this->peekNonBlank();
        if ($idx === null) {
            return false;
        }
        $line = $this->lines[$idx];
        if ($this->depthOf($line) !== $childDepth) {
            return false;
        }
        $content = $line['content'];
        if ($tabular) {
            return !$this->isKeyValueLine($content, $delim);
        }
        return $content === '-' || str_starts_with($content, '- ');
    }

    /**
     * Position the cursor on the next array element, handling blank lines.
     * Returns false if there is no further element line.
     */
    private function advanceToArrayElement(int $childDepth): bool
    {
        while ($this->pos < count($this->lines) && $this->lines[$this->pos]['blank']) {
            if ($this->strict) {
                throw new DecodeException('Blank line inside array', $this->lines[$this->pos]['no']);
            }
            $this->pos++; // non-strict: ignore blank line, do not count it
        }
        return $this->pos < count($this->lines);
    }

    // ------------------------------------------------------------------
    // Header parsing (§6)
    // ------------------------------------------------------------------

    /**
     * Attempt to parse $content as an array header. Returns null when it is not a
     * (valid) header; the caller decides strict-error vs key-value fall-through.
     *
     * @return array{keyKind: string, key: string, keyQuoted: bool, n: int, delim: string, fields: ?list<array{name:string,quoted:bool}>, rest: string}|null
     */
    private function tryHeader(string $content): ?array
    {
        $i = 0;
        $len = strlen($content);
        $key = '';
        $keyQuoted = false;
        $keyKind = 'none';

        if ($len > 0 && $content[0] === '"') {
            $res = $this->scanQuoted($content, 0, false);
            if ($res === null) {
                return null;
            }
            [$key, $i] = $res;
            $keyQuoted = true;
            $keyKind = 'present';
        } else {
            $bracket = strpos($content, '[');
            if ($bracket === false) {
                return null;
            }
            $key = substr($content, 0, $bracket);
            if ($key !== '') {
                if (preg_match(self::UNQUOTED_KEY, $key) !== 1) {
                    return null;
                }
                $keyKind = 'present';
            }
            $i = $bracket;
        }

        if ($i >= $len || $content[$i] !== '[') {
            return null;
        }
        $close = strpos($content, ']', $i);
        if ($close === false) {
            return null;
        }
        $inner = substr($content, $i + 1, $close - $i - 1);
        if (preg_match('/^(0|[1-9]\d*)([\t|])?$/', $inner, $m) !== 1) {
            return null;
        }
        $n = (int) $m[1];
        $delim = ($m[2] ?? '') !== '' ? $m[2] : ',';

        $j = $close + 1;
        $fields = null;
        if ($j < $len && $content[$j] === '{') {
            $braceClose = $this->matchBrace($content, $j);
            if ($braceClose === null) {
                return null;
            }
            $fieldsRaw = substr($content, $j + 1, $braceClose - $j - 1);
            $fields = $this->parseFieldNames($fieldsRaw, $delim);
            if ($fields === null) {
                return null;
            }
            $j = $braceClose + 1;
        }

        if ($j >= $len || $content[$j] !== ':') {
            return null; // missing colon / content between segments
        }
        $rest = substr($content, $j + 1);

        return [
            'keyKind' => $keyKind,
            'key' => $key,
            'keyQuoted' => $keyQuoted,
            'n' => $n,
            'delim' => $delim,
            'fields' => $fields,
            'rest' => $rest,
            'lineNo' => null,
        ];
    }

    /** Does $content begin a header attempt (an unquoted '[' before the first unquoted ':')? */
    private function hasHeaderAttempt(string $content): bool
    {
        // Skip a leading quoted key, if any.
        $start = 0;
        if (isset($content[0]) && $content[0] === '"') {
            $res = $this->scanQuoted($content, 0, false);
            if ($res === null) {
                return false;
            }
            $start = $res[1];
        }
        $bracket = strpos($content, '[', $start);
        if ($bracket === false) {
            return false;
        }
        $colon = $this->firstUnquotedColon($content);
        return $colon === -1 || $bracket < $colon;
    }

    /** Match the closing brace for a fields segment, respecting quoted names. */
    private function matchBrace(string $content, int $open): ?int
    {
        $len = strlen($content);
        $i = $open + 1;
        while ($i < $len) {
            $ch = $content[$i];
            if ($ch === '"') {
                $res = $this->scanQuoted($content, $i, false);
                if ($res === null) {
                    return null;
                }
                $i = $res[1];
                continue;
            }
            if ($ch === '}') {
                return $i;
            }
            $i++;
        }
        return null;
    }

    /**
     * @return list<array{name:string,quoted:bool}>|null
     */
    private function parseFieldNames(string $raw, string $delim): ?array
    {
        $tokens = $this->splitDelimited($raw, $delim, false);
        $fields = [];
        foreach ($tokens as $token) {
            if ($token !== '' && $token[0] === '"') {
                $res = $this->scanQuoted($token, 0, true);
                if ($res === null || $res[1] !== strlen($token)) {
                    return null;
                }
                $fields[] = ['name' => $res[0], 'quoted' => true];
            } else {
                if (preg_match(self::UNQUOTED_KEY, $token) !== 1) {
                    return null; // invalid unquoted field name (also catches delimiter mismatch)
                }
                $fields[] = ['name' => $token, 'quoted' => false];
            }
        }
        return $fields;
    }

    // ------------------------------------------------------------------
    // Key / value tokenizing
    // ------------------------------------------------------------------

    /**
     * Split "key: value" into [key, quoted, rest, found].
     *
     * @return array{0: string, 1: bool, 2: string, 3: bool}
     */
    private function splitKeyColon(string $content): array
    {
        if (isset($content[0]) && $content[0] === '"') {
            $res = $this->scanQuoted($content, 0, false);
            if ($res !== null) {
                [$key, $after] = $res;
                if (isset($content[$after]) && $content[$after] === ':') {
                    return [$key, true, substr($content, $after + 1), true];
                }
            }
            return ['', false, '', false];
        }
        $colon = $this->firstUnquotedColon($content);
        if ($colon === -1) {
            return ['', false, '', false];
        }
        return [substr($content, 0, $colon), false, substr($content, $colon + 1), true];
    }

    /** Index of the first unquoted ':' in $content, or -1. */
    private function firstUnquotedColon(string $content): int
    {
        $len = strlen($content);
        $inQuotes = false;
        for ($i = 0; $i < $len; $i++) {
            $ch = $content[$i];
            if ($ch === '"') {
                $inQuotes = !$inQuotes;
            } elseif ($ch === '\\' && $inQuotes) {
                $i++; // skip escaped char
            } elseif ($ch === ':' && !$inQuotes) {
                return $i;
            }
        }
        return -1;
    }

    /** §9.3 disambiguation: is a row-depth line a key-value line (ends rows)? */
    private function isKeyValueLine(string $content, string $delim): bool
    {
        $len = strlen($content);
        $inQuotes = false;
        $colon = -1;
        $delimPos = -1;
        for ($i = 0; $i < $len; $i++) {
            $ch = $content[$i];
            if ($ch === '"') {
                $inQuotes = !$inQuotes;
            } elseif ($ch === '\\' && $inQuotes) {
                $i++;
            } elseif (!$inQuotes) {
                if ($ch === ':' && $colon === -1) {
                    $colon = $i;
                } elseif ($ch === $delim && $delimPos === -1) {
                    $delimPos = $i;
                }
            }
        }
        if ($colon === -1) {
            return false; // no colon -> row
        }
        if ($delimPos === -1) {
            return true; // colon, no delimiter -> key-value
        }
        return $colon < $delimPos; // colon before delimiter -> key-value
    }

    /**
     * Split a delimited inline string into trimmed tokens, respecting quotes.
     *
     * @return list<string>
     */
    private function splitDelimited(string $s, string $delim, bool $trim = true): array
    {
        $tokens = [];
        $cur = '';
        $len = strlen($s);
        $inQuotes = false;
        for ($i = 0; $i < $len; $i++) {
            $ch = $s[$i];
            if ($ch === '"') {
                $inQuotes = !$inQuotes;
                $cur .= $ch;
            } elseif ($ch === '\\' && $inQuotes) {
                $cur .= $ch;
                if ($i + 1 < $len) {
                    $cur .= $s[$i + 1];
                    $i++;
                }
            } elseif ($ch === $delim && !$inQuotes) {
                $tokens[] = $trim ? trim($cur) : $cur;
                $cur = '';
            } else {
                $cur .= $ch;
            }
        }
        $tokens[] = $trim ? trim($cur) : $cur;
        return $tokens;
    }

    /** Parse a single primitive cell/token (quoted string or coerced scalar). */
    private function parsePrimitiveToken(string $token): mixed
    {
        $token = trim($token);
        if ($token === '') {
            return ''; // empty token -> empty string (§9.1)
        }
        if ($token[0] === '"') {
            $res = $this->scanQuoted($token, 0, true);
            if ($res === null || $res[1] !== strlen($token)) {
                throw new DecodeException('Malformed quoted string', null, $token);
            }
            return $res[0];
        }
        return $this->coerceScalar($token);
    }

    /** Parse an object field's scalar value (single token, quoting-aware, not delimiter-split). */
    private function parseScalarValue(string $rest): mixed
    {
        $trim = trim($rest);
        if ($trim !== '' && $trim[0] === '"') {
            $res = $this->scanQuoted($trim, 0, true);
            if ($res === null || $res[1] !== strlen($trim)) {
                throw new DecodeException('Malformed quoted string', null, $trim);
            }
            return $res[0];
        }
        return $this->coerceScalar($trim);
    }

    private function coerceScalar(string $s): mixed
    {
        if ($s === 'true') {
            return true;
        }
        if ($s === 'false') {
            return false;
        }
        if ($s === 'null') {
            return null;
        }
        if (preg_match(self::NUMBER, $s) === 1) {
            $f = (float) $s;
            if (is_finite($f) && $f === floor($f) && abs($f) < 9.2e18) {
                return (int) $f; // integral -> int (also -0 -> 0)
            }
            return $f;
        }
        return $s;
    }

    /**
     * Scan a quoted string beginning at $start (content[$start] === '"').
     * Returns [decodedValue, indexAfterClosingQuote] or null on a structural failure
     * for tryHeader/splitKeyColon. When $throwOnError is true, escape/termination
     * problems raise DecodeException instead of returning null.
     *
     * @return array{0: string, 1: int}|null
     */
    private function scanQuoted(string $s, int $start, bool $throwOnError): ?array
    {
        $len = strlen($s);
        $i = $start + 1;
        $out = '';
        while ($i < $len) {
            $ch = $s[$i];
            if ($ch === '"') {
                return [$out, $i + 1];
            }
            if ($ch === '\\') {
                if ($i + 1 >= $len) {
                    break;
                }
                $esc = $s[$i + 1];
                switch ($esc) {
                    case '\\':
                        $out .= '\\';
                        $i += 2;
                        break;
                    case '"':
                        $out .= '"';
                        $i += 2;
                        break;
                    case 'n':
                        $out .= "\n";
                        $i += 2;
                        break;
                    case 'r':
                        $out .= "\r";
                        $i += 2;
                        break;
                    case 't':
                        $out .= "\t";
                        $i += 2;
                        break;
                    case 'u':
                        $hex = substr($s, $i + 2, 4);
                        if (strlen($hex) !== 4 || preg_match('/^[0-9A-Fa-f]{4}$/', $hex) !== 1) {
                            return $this->fail('Invalid \\u escape', $throwOnError, $s);
                        }
                        $cp = hexdec($hex);
                        if ($cp >= 0xD800 && $cp <= 0xDFFF) {
                            return $this->fail('Lone surrogate in \\u escape', $throwOnError, $s);
                        }
                        $out .= mb_chr($cp, 'UTF-8');
                        $i += 6;
                        break;
                    default:
                        return $this->fail('Invalid escape sequence \\' . $esc, $throwOnError, $s);
                }
                continue;
            }
            $out .= $ch;
            $i++;
        }
        return $this->fail('Unterminated string', $throwOnError, $s);
    }

    /**
     * @return null
     */
    private function fail(string $message, bool $throwOnError, string $snippet)
    {
        if ($throwOnError) {
            throw new DecodeException($message, null, $snippet);
        }
        return null;
    }

    // ------------------------------------------------------------------
    // Finalization (RawObject -> stdClass) and path expansion (§13.4)
    // ------------------------------------------------------------------

    private function finalize(mixed $node): mixed
    {
        if ($node instanceof RawObject) {
            return $this->expandPaths === 'safe'
                ? $this->finalizeWithExpansion($node)
                : $this->finalizePlain($node);
        }
        if (is_array($node)) {
            return array_map(fn($v) => $this->finalize($v), $node);
        }
        return $node;
    }

    private function finalizePlain(RawObject $node): \stdClass
    {
        $o = new \stdClass();
        foreach ($node->entries as $entry) {
            $o->{$entry['k']} = $this->finalize($entry['v']);
        }
        return $o;
    }

    private function finalizeWithExpansion(RawObject $node): \stdClass
    {
        $o = new \stdClass();
        foreach ($node->entries as $entry) {
            $value = $this->finalize($entry['v']);
            $segments = (!$entry['q'] && $this->shouldExpand($entry['k']))
                ? explode('.', $entry['k'])
                : [$entry['k']];
            $this->mergeInto($o, $segments, $value);
        }
        return $o;
    }

    private function shouldExpand(string $key): bool
    {
        if (!str_contains($key, '.')) {
            return false;
        }
        foreach (explode('.', $key) as $seg) {
            if (preg_match(self::IDENTIFIER_SEG, $seg) !== 1) {
                return false;
            }
        }
        return true;
    }

    /** @param list<string> $segments */
    private function mergeInto(\stdClass $obj, array $segments, mixed $value): void
    {
        $cur = $obj;
        $count = count($segments);
        foreach ($segments as $i => $seg) {
            $last = $i === $count - 1;
            if ($last) {
                if (property_exists($cur, $seg)) {
                    $existing = $cur->{$seg};
                    if ($existing instanceof \stdClass && $value instanceof \stdClass) {
                        $this->deepMerge($existing, $value);
                    } elseif ($this->strict) {
                        throw new DecodeException(sprintf("Expansion conflict at path '%s'", $seg));
                    } else {
                        $cur->{$seg} = $value; // LWW
                    }
                } else {
                    $cur->{$seg} = $value;
                }
                return;
            }
            if (property_exists($cur, $seg)) {
                if ($cur->{$seg} instanceof \stdClass) {
                    $cur = $cur->{$seg};
                } elseif ($this->strict) {
                    throw new DecodeException(sprintf("Expansion conflict at path '%s'", $seg));
                } else {
                    $cur->{$seg} = new \stdClass(); // LWW: replace non-object with object
                    $cur = $cur->{$seg};
                }
            } else {
                $next = new \stdClass();
                $cur->{$seg} = $next;
                $cur = $next;
            }
        }
    }

    private function deepMerge(\stdClass $target, \stdClass $source): void
    {
        foreach (get_object_vars($source) as $k => $v) {
            if (property_exists($target, $k)) {
                $tv = $target->{$k};
                if ($tv instanceof \stdClass && $v instanceof \stdClass) {
                    $this->deepMerge($tv, $v);
                } elseif ($this->strict) {
                    throw new DecodeException(sprintf("Expansion conflict at path '%s'", $k));
                } else {
                    $target->{$k} = $v;
                }
            } else {
                $target->{$k} = $v;
            }
        }
    }
}
