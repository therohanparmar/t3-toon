<?php

declare(strict_types=1);

namespace RRP\T3Toon\Service;

use RRP\T3Toon\Domain\Model\DecodeOptions;
use RRP\T3Toon\Exception\ToonDecodeException;
use RRP\T3Toon\Utility\ToonHelper;

class ToonDecoder
{
    protected array $config;

    public function __construct()
    {
        $this->config = ToonHelper::getConfig();
    }

    /**
     * @param DecodeOptions|null $options Optional overrides (e.g. coerceScalarTypes); null = extension config
     * @throws ToonDecodeException When the TOON input is malformed
     */
    public function fromToon(string $toon, ?DecodeOptions $options = null): array
    {
        $previousConfig = $this->config;
        try {
            if ($options !== null) {
                $this->config = ToonHelper::getConfigMerged($options->toConfigOverrides());
            }

            // Split TOON into individual lines, handling both \n and \r\n endings.
        $lines = preg_split("/\r?\n/", $toon);

        // Root container that holds the decoded structure.
        $root = [];

        // Stack of container references for nested structures.
        $stack = [&$root];

        // Stack that tracks indentation depth to handle nested mappings and arrays.
        $indentStack = [-1];

        // Track keys at each level to detect when an object should pivot into a list.
        $seenKeysStack = [[]];

        // Iterate through each line in the TOON input.
        foreach ($lines as $lineIndex => $rawLine) {
            $lineNumber = $lineIndex + 1;
            if ($rawLine === null) {
                continue;
            }
            $line = rtrim($rawLine, "\r\n");

            // Skip blank lines safely.
            if (trim($line) === '') {
                continue;
            }

            // Count indentation (spaces) to determine current nesting level.
            $indent = strlen($line) - strlen(ltrim($line, ' '));
            $content = trim($line);

            // Reduce nesting if current indentation is less than the last stored level.
            while (count($indentStack) > 1 && $indent <= end($indentStack)) {
                array_pop($indentStack);
                array_pop($stack);
                array_pop($seenKeysStack);
            }

            $current = &$stack[count($stack) - 1];

            // Primitive array: key[N]: v1,v2,v3 or (at root) [N]: v1,v2,v3
            if (preg_match('/^([A-Za-z0-9_\-\.]+)\[(\d+)\]:\s*(.*)$/s', $content, $primM)) {
                $key = strtolower($primM[1]);
                $count = (int) $primM[2];
                $rest = trim($primM[3]);
                $cells = $rest !== '' ? $this->splitCsvEscaped($rest) : [];
                $values = array_map(fn($c) => $this->coerceValue($c), $cells);
                $current[$key] = array_slice($values, 0, $count);
                continue;
            }
            if (preg_match('/^\[(\d+)\]:\s*(.*)$/s', $content, $rootPrimM)) {
                $count = (int) $rootPrimM[1];
                $rest = trim($rootPrimM[2]);
                $cells = $rest !== '' ? $this->splitCsvEscaped($rest) : [];
                $values = array_map(fn($c) => $this->coerceValue($c), array_slice($cells, 0, $count));
                foreach ($values as $v) {
                    $current[] = $v;
                }
                continue;
            }

            if (preg_match('/^items\[(\d+)\]\{([^\}]*)\}:$/', $content, $m)) {
                $expectedCount = (int) $m[1];
                $fieldList = array_map('trim', array_filter(array_map('trim', explode(',', $m[2])), function ($v) {
                    return $v !== '';
                }));

                // Prepare a placeholder container for this TOON table block.
                $tableContainer = ['__table__' => ['count' => $expectedCount, 'fields' => $fieldList, 'rows' => []]];

                // Attach this table to the current structure (root or nested).
                $current[] = $tableContainer;

                // Push new reference context for the rows block.
                $stack[] = &$current[count($current) - 1];
                $indentStack[] = $indent;
                $seenKeysStack[] = [];
                continue;
            }

            // Handle row entries within a TOON table.
            if (isset($current['__table__'])) {
                $rowText = trim($content);
                if ($rowText !== '') {
                    // Split fields using escaped CSV logic to preserve commas and special chars.
                    $rowCells = $this->splitCsvEscaped($rowText);
                    $fields = $current['__table__']['fields'];

                    // Build associative array row: field => value
                    $rowObject = [];
                    foreach ($fields as $i => $field) {
                        $rowObject[$field] = $this->coerceValue($rowCells[$i] ?? '');
                    }

                    // Add parsed row into table rows.
                    $current['__table__']['rows'][] = $rowObject;
                    continue;
                }
            }

            // Line contains colon but invalid key:value format (e.g. "key : value" or ": value")
            if (strpos($content, ':') !== false && !preg_match('/^([A-Za-z0-9_\-\.]+):(?:\s*(.*))?$/', $content)) {
                throw new ToonDecodeException(
                    'Invalid key:value format (key must be identifier, no space before colon)',
                    $lineNumber,
                    $content,
                );
            }

            if (preg_match('/^([A-Za-z0-9_\-\.]+):(?:\s*(.*))?$/', $content, $mm)) {
                $key = strtolower($mm[1]);
                $val = $mm[2] ?? null;

                // Pivot Logic: If key repeats at same level (e.g. 'id'), convert parent to list.
                if (in_array($key, $seenKeysStack[count($seenKeysStack) - 1])) {
                    array_pop($stack);
                    $parent = &$stack[count($stack) - 1];
                    $parentKey = array_key_last($parent);
                    if (!isset($parent[$parentKey][0])) {
                        $parent[$parentKey] = [$parent[$parentKey]];
                    }
                    $parent[$parentKey][] = [];
                    $stack[] = &$parent[$parentKey][array_key_last($parent[$parentKey])];
                    $current = &$stack[count($stack) - 1];
                    $seenKeysStack[count($seenKeysStack) - 1] = [];
                }

                $seenKeysStack[count($seenKeysStack) - 1][] = $key;

                // If value is empty, expect a nested block below this line.
                if ($val === null || trim($val) === '') {
                    $current[$key] = [];
                    // Push new reference level to handle indented child elements.
                    $stack[] = &$current[$key];
                    $indentStack[] = $indent;
                    $seenKeysStack[] = [];
                } else {
                    // Simple scalar value line, coerce type and assign.
                    $current[$key] = $this->coerceValue($this->unescape($val));
                }
                continue;
            }

            // If a line does not fit any pattern, handle as sequential item or throw exception.
            if ($indent > (end($indentStack) ?? -1)) {
                $current[] = $this->coerceValue($this->unescape($content));
                continue;
            }

            throw new ToonDecodeException(
                "Malformed TOON line at indent {$indent}",
                $lineNumber,
                $content !== '' ? $content : null,
            );
        }

            // Recursively finalize and normalize any embedded tables.
            return $this->finalizeTables($root);
        } finally {
            $this->config = $previousConfig;
        }
    }

    /**
     * Converts internal table markers into finalized array structures.
     * Example: Converts `['__table__' => ['rows' => [...]]]` → `[ [...], [...], ... ]`
     */
    protected function finalizeTables(array $node)
    {
        foreach ($node as $k => $v) {
            if (is_array($v)) {
                if (isset($v['__table__'])) {
                    $node[$k] = $v['__table__']['rows'];
                } else {
                    $node[$k] = $this->finalizeTables($v);
                }
            }
        }
        return $node;
    }

    /**
     * Splits a CSV line into fields, respecting backslash-escaped commas.
     */
    protected function splitCsvEscaped(string $s): array
    {
        $result = preg_split('/(?<!\\\\),/', $s);
        return array_map(fn($p) => $this->unescape(trim($p)), $result);
    }

    /**
     * Decodes backslash-escaped sequences (\n, \:, \,) into their literal forms.
     */
    protected function unescape(string $s): string
    {
        if ($this->config['escape_style'] === 'backslash') {
            return str_replace(['\\n', '\\:', '\\,', '\\\\'], ["\n", ':', ',', '\\'], $s);
        }
        return str_replace('\\n', "\n", $s);
    }

    /**
     * Coerces string values into appropriate scalar types based on configuration.
     * - "true" → boolean true
     * - "42" → integer 42
     * - "3.14" → float 3.14
     * - "" → null
     */
    protected function coerceValue(string $s)
    {
        $s = trim($s);
        if ($s === '')
            return null;

        if ($this->config['coerce_scalar_types']) {
            $lower = strtolower($s);
            if ($lower === 'true')
                return true;
            if ($lower === 'false')
                return false;
            if ($lower === 'null')
                return null;
            if (is_numeric($s)) {
                return strpos($s, '.') !== false ? (float) $s : (int) $s;
            }
        }
        return $s;
    }
}
