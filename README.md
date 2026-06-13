# TOON for TYPO3

### Token-Optimized Object Notation for AI & LLM Workflows

<p align="center">
  <img src="https://img.shields.io/badge/Version-4.0.0-blue" alt="Version 4.0.0">
  <img src="https://img.shields.io/github/license/therohanparmar/t3-toon" alt="License">
  <img src="https://img.shields.io/badge/TYPO3-12,13,14-orange" alt="TYPO3 12-14">
  <img src="https://img.shields.io/badge/PHP-8.1%2B-red" alt="PHP 8.1+">
  <a title="Crowdin" target="_blank" href="https://crowdin.com/project/typo3-extension-rrpt3toon"><img src="https://badges.crowdin.net/typo3-extension-rrpt3toon/localized.svg"></a>
</p>

---

> **v4.0 — now fully spec-compliant.** This release implements the official [TOON Specification v3.3](https://github.com/toon-format/spec) and passes the complete language-agnostic conformance suite (389/389 encode + decode fixtures). See [Specification compliance](#-specification-compliance) below. v4.0 is a breaking change from the 3.x custom format — review the migration notes before upgrading.

## ✨ What is TOON?

**TOON (Token-Oriented Object Notation)** is a **compact, human-readable, token-efficient** text format for the JSON data model, purpose-built for **AI prompts and LLM contexts**. This extension is a spec-compliant TOON encoder/decoder for TYPO3.

It helps you:

- 🔻 Reduce token usage (up to 60–75%)
- 💰 Lower AI API costs
- 🧠 Improve prompt clarity and context understanding
- 🔁 Convert data seamlessly between **JSON ⇄ TOON**

---

## 🚀 Key Features

- 🔁 Bidirectional conversion (JSON ⇄ TOON)
- 🧩 Compact, YAML-like and human-readable format
- 💰 Significant token and size reduction
- 📊 Built-in analytics and token estimation
- 🧠 Optimized for ChatGPT, Gemini, Claude, and Mistral
- 🆕 Supports deeply nested and complex data structures
- 🔒 Preserves key order and data integrity
- 🗂️ Usage logs backend module — every encode/convert call is recorded with input size, output size, and optimization %, filterable and bulk-deletable

---

## 📦 Installation

### ➤ TYPO3 Extension Repository (TER)

Install via the TYPO3 backend or directly from TER:

🔗 https://extensions.typo3.org/extension/rrp_t3toon

---

### ➤ Composer (Packagist)

Recommended for Composer-based TYPO3 projects: 🔗 https://packagist.org/packages/rrp/t3-toon

```bash
composer require rrp/t3-toon
```

## 🧠 Quick Usage Example

**Static API (convenience, no DI):**

```php
use RRP\T3Toon\Service\Toon;

$data = ['user' => 'ABC', 'tasks' => [['id' => 1, 'done' => false], ['id' => 2, 'done' => true]]];
echo Toon::encodeStatic($data);
// or: Toon::convertStatic($data);

$decoded = Toon::decodeStatic($toonString);
$estimate = Toon::estimateTokensStatic($toonString);
```

**Instance API (recommended in TYPO3 for dependency injection):**

```php
use RRP\T3Toon\Service\Toon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$toon = GeneralUtility::makeInstance(Toon::class);
echo $toon->convert($data);
$decoded = $toon->decode($toonString);
```

**Output (TOON):**

```
user: ABC
tasks:
  items[2]{id,done}:
    1,false
    2,true
```

### Configuration options

Override encoding/decoding per call with **EncodeOptions** and **DecodeOptions** (null = use extension config from Install Tool):

```php
use RRP\T3Toon\Domain\Model\EncodeOptions;
use RRP\T3Toon\Domain\Model\DecodeOptions;
use RRP\T3Toon\Service\Toon;

// Encoding: indent, delimiter (comma/tab/pipe), key folding
$compact = Toon::encodeStatic($data, EncodeOptions::compact());   // indent 2, comma
$readable = Toon::encodeStatic($data, EncodeOptions::readable()); // indent 4
$tabular = Toon::encodeStatic($data, EncodeOptions::tabular());   // tab delimiter
$folded = Toon::encodeStatic($data, EncodeOptions::folded());     // safe key folding
$custom = Toon::encodeStatic($data, new EncodeOptions(indent: 2, delimiter: "|", keyFolding: "safe"));

// Decoding: strict (default) vs lenient; optional dotted-key path expansion
$data = Toon::decodeStatic($toon);
$lenient = Toon::decodeStatic($toon, DecodeOptions::lenient());   // relax strict-mode checks
$expanded = Toon::decodeStatic($toon, DecodeOptions::expanded()); // expand a.b.c into nested objects
```

Extension config (Install Tool → Settings → Extension Configuration → rrp_t3toon) provides defaults:

| Key | Values | Default | Applies to |
| --- | --- | --- | --- |
| `enabled` | bool | `1` | encode (passthrough when off) |
| `indent` | int ≥ 1 | `2` | encode |
| `delimiter` | comma / tab / pipe | `comma` | encode |
| `key_folding` | off / safe | `off` | encode (§13.4) |
| `flatten_depth` | int (`-1` = unbounded) | `-1` | encode (key folding) |
| `strict` | bool | `1` | decode (§14) |
| `expand_paths` | off / safe | `off` | decode (§13.4) |
| `show_default_example` | bool | `1` | Playground (prefill sample on first load) |

**Note on `enabled`:** when this flag is turned off, `Toon::encode()` and `Toon::convert()` short-circuit and return the input as-is (string verbatim, or `json_encode($input)` for arrays/objects). This lets you toggle optimization globally without code changes. Calls are still logged so you can see in the Logs module which requests ran with optimization on vs off.

### Global helpers

When the extension is loaded, these global functions are available (no `use` needed):

```php
toon($value);                    // encode (alias for Toon::encodeStatic)
toon_decode($toon);              // decode
toon_compact($value);            // encode with EncodeOptions::compact()
toon_readable($value);           // encode with EncodeOptions::readable()
toon_decode_lenient($toon);      // decode without scalar coercion
toon_estimate_tokens($toon);      // return ['words' => …, 'chars' => …, 'tokens_estimate' => …]
```

### Fluid ViewHelpers

In Fluid templates, add the namespace and use:

```html
<html xmlns:toon="http://typo3.org/ns/RRP/T3Toon/ViewHelpers" data-namespace-typo3-fluid="true">
  <!-- Encode data to TOON -->
  <toon:encode value="{data}" />
  <toon:encode value="{data}" options="readable" />
  <toon:encode value="{data}" options="compact" />

  <!-- Decode TOON and use in loop -->
  <toon:decode toon="{toonString}" as="decoded">
    <f:for each="{decoded}" as="item">…</f:for>
  </toon:decode>

  <!-- Estimate tokens -->
  <toon:estimateTokens toon="{toonString}" />
  <toon:estimateTokens toon="{toonString}" as="stats">{stats.tokens_estimate}</toon:estimateTokens>
</html>
```

### Backend modules

#### Tools → TOON Playground

Encode and decode TOON in the browser:

- Paste **JSON** and click **Encode to TOON** or **Encode (compact)** to get TOON output.
- Paste **TOON** and click **Decode from TOON** to get JSON.
- The module shows estimated tokens and any error messages.
- On first load it pre-fills a sample JSON input and its encoded TOON output so you can see the format immediately. Turn this off with the `show_default_example` extension setting (on by default); **Clear all** empties both fields.

#### TOON Logs (View Logs button)

The Logs screen is part of the same module — open it with the **View Logs** button in the Playground header (and **Back to Playground** to return); there is no separate menu entry.

Every successful `encode` / `convert` call (from anywhere — Playground, ViewHelpers, helper functions, programmatic API, scheduler tasks, CLI commands) is recorded in `tx_rrpt3toon_log`. The screen lists those rows with:

- Filters: date range, optimization status (enabled/disabled), minimum optimization %, page size.
- Per-row optimization badge (green when bytes were saved, neutral when passthrough).
- Per-row delete + bulk delete (checkbox each row + select-all in the header).
- Sliding-window pagination, newest entries first.

The schema must be migrated once after installation:

```bash
vendor/bin/typo3 database:updateschema "*.add,*.change"
```

For development / demos a CLI seed command is available:

```bash
vendor/bin/typo3 t3toon:seed-logs 500    # insert 500 random rows spread over the last 30 days
vendor/bin/typo3 t3toon:seed-logs 100 7  # 100 rows over the last 7 days
```

### Error handling

Decoding malformed TOON throws `RRP\T3Toon\Exception\ToonDecodeException` with line number and snippet:

```php
use RRP\T3Toon\Exception\ToonDecodeException;
use RRP\T3Toon\Service\Toon;

try {
    $data = Toon::decodeStatic($input);
} catch (ToonDecodeException $e) {
    // $e->getLineNumber(), $e->getSnippet()
}
```

---

## 📚 Documentation

Full documentation, configuration, and advanced usage are available here:

🔗 https://docs.typo3.org/p/rrp/t3-toon/main/en-us/

---

## 🧩 Use Cases

- 🤖 AI prompt engineering
- 📉 Token and cost optimization
- 🧠 Structured data preprocessing
- 🧾 Compact logging and debugging
- 🗄️ Optimized JSON storage
- 🔍 Developer tooling and previews

---

## 🧰 Compatibility

| TYPO3       | PHP   | Extension Version | TOON Spec |
| ----------- | ----- | ----------------- | --------- |
| 12.x – 14.x | ≥ 8.1 | v4.0.0            | v3.3      |

## ✅ Specification compliance

As of **v4.0.0**, this extension implements the official **[TOON Specification v3.3](https://github.com/toon-format/spec)** and passes the complete language-agnostic conformance suite — **389/389 fixtures** (153 encode + 236 decode), covering primitives, objects, primitive/tabular/nested/mixed arrays, all three delimiters (comma, tab, pipe), quoting and escaping rules, canonical number formatting, root-form detection, strict-mode validation errors, blank-line and indentation rules, key folding, and path expansion.

The vendored fixtures live in `Tests/Fixtures/spec/` (see `PROVENANCE.txt` for the pinned spec commit). Run them with:

```bash
php Tests/run-conformance.php          # standalone runner, no dependencies
# or, with the dev dependencies installed:
composer test:unit                     # includes the PHPUnit ConformanceTest
```

The spec engine lives in `Classes/Spec/` (`Encoder`, `Decoder`) and is dependency-free; the TYPO3 service layer (`Toon`, `ToonEncoder`, `ToonDecoder`) is a thin adapter over it.

### Upgrading from 3.x (breaking changes)

v3.x emitted a TYPO3-specific “TOON-inspired” format. v4.0 emits **standards-compliant TOON**, so output bytes differ. Notable changes:

- Tabular arrays use the real key (`users[2]{id,name}:`) instead of a hardcoded `items[…]` wrapper.
- Object keys are preserved verbatim (no lowercasing/stripping); strings are quoted/escaped per spec instead of being trimmed.
- Primitive arrays are inline by default (`tags[3]: a,b,c`); `null` encodes as `null`.
- Removed config keys: `escape_style`, `min_rows_to_tabular`, `max_preview_items`, `coerce_scalar_types`, `primitive_array_header`. New keys: `key_folding`, `flatten_depth`, `strict`, `expand_paths`.
- `DecodeOptions::lenient()` / the `lenient` ViewHelper argument now mean **non-strict decoding** (not “skip type coercion”). `Toon::decode()` now returns `mixed` (root scalars/arrays are supported), with objects still decoded to associative arrays.

---

## 👨‍💻 Authors

- **[Rohan Parmar](https://www.linkedin.com/in/rohanrparmar)**
- **[Himanshu Ramavat](https://www.linkedin.com/in/himanshu-ramavat/)**

---

## 💡 Contributing

Contributions are welcome and appreciated ❤️

- Fork the repository
- Create a feature branch
- Commit your changes
- Submit a Pull Request

---

## 📜 License

Licensed under the MIT License — free for personal and commercial use.

---

<p align="center">
  <b>Made with 🧡 for the TYPO3 Developer</b>
</p>
