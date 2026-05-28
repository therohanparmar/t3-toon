.. _features:

Key Features
============

Bidirectional Conversion
-------------------------

Convert between JSON and TOON format seamlessly:

* **JSON → TOON**: Compress data for AI prompts (encode/convert)
* **TOON → JSON**: Restore original structure (decode)

Readable & Compact
------------------

* YAML-like structure that's human-friendly
* Configurable indent and delimiter (comma or tab)
* Presets: compact (indent 0), readable (indent 4), tabular (tab delimiter)

Token-Efficient
---------------

* Save up to 70% tokens on every AI prompt
* Reduces API costs significantly
* Optimized for ChatGPT, Gemini, Claude, and Mistral models
* Built-in token estimation (words, chars, tokens_estimate)

Preserves Key Order
-------------------

* Ensures deterministic data output
* Maintains structure integrity
* Critical for consistent AI responses

Per-Call Options (EncodeOptions / DecodeOptions)
------------------------------------------------

* **EncodeOptions**: Override indent, delimiter, maxPreviewItems, escapeStyle, minRowsToTabular, primitiveArrayHeader per call
* **DecodeOptions**: Control scalar type coercion (default vs lenient)
* Presets: ``EncodeOptions::compact()``, ``EncodeOptions::readable()``, ``EncodeOptions::tabular()``; ``DecodeOptions::lenient()``

Static and Instance API
------------------------

* **Static API**: ``Toon::encodeStatic()``, ``Toon::decodeStatic()``, ``Toon::convertStatic()``, ``Toon::estimateTokensStatic()`` — no dependency injection required
* **Instance API**: ``$toon->encode()``, ``$toon->decode()``, etc. — recommended for TYPO3 DI and testing

Error Handling
--------------

* **ToonDecodeException** for malformed TOON input, with ``getLineNumber()`` and ``getSnippet()`` for easier debugging
* Base **ToonException** for all TOON-related errors

Global Helpers
--------------

* ``toon()``, ``toon_decode()``, ``toon_compact()``, ``toon_readable()``, ``toon_decode_lenient()``, ``toon_estimate_tokens()`` — no ``use`` required after extension load

Fluid ViewHelpers
-----------------

* **toon:encode**, **toon:decode**, **toon:estimateTokens** — use TOON directly in Fluid templates with optional presets (default, compact, readable, tabular; lenient for decode)

Backend Module (TOON Playground)
--------------------------------

* **Tools → TOON Playground**: Encode JSON to TOON, decode TOON to JSON, encode compact, view token estimate and errors in the browser

Usage Logs Module
-----------------

* **Tools → TOON Logs**: Every successful ``encode`` / ``convert`` call is recorded in ``tx_rrpt3toon_log`` (input size, output size, optimization %, ``settings_enabled`` snapshot, timestamp)
* Filters: date range, optimization status (enabled / disabled / all), minimum optimization %, page size
* Per-row delete and bulk delete with confirmation modal
* Sliding-window pagination, newest entries first
* CLI seed command for development: ``vendor/bin/typo3 t3toon:seed-logs <count> [days]``

Global "enabled" Flag
---------------------

* Extension setting ``enabled`` toggles optimization globally without code changes
* When off, ``Toon::encode()`` / ``Toon::convert()`` return the input as-is; calls are still logged so you can audit which requests ran with optimization on vs off

Built-in Analytics
------------------

* Measure token, byte, and compression performance via ``estimateTokens()`` / ``estimateTokensStatic()``
* Compare JSON vs TOON metrics

Complex Nested Array Support
-----------------------------

* Fully supports deeply nested associative and indexed arrays
* Handles multi-level structures
* Optional primitive array header ``[N]: v1,v2,v3`` for spec-style output

TYPO3 Integration
-----------------

* Native TYPO3 extension
* Follows TYPO3 coding standards
* Dependency injection support
* Service-based architecture
* Extension configuration (Install Tool) and programmatic options
