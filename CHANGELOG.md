# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added (Phase 3 – Batch 2: A + D)

- **Primitive array header** (A): `EncodeOptions::withPrimitiveArrayHeader(true)` and config `primitive_array_header` to emit spec-style `[N]: v1,v2,v3` for primitive sequential arrays; decoder parses `[N]:` and `key[N]:` lines.
- **Backend module TOON Playground** (D): New module under **Tools → TOON Playground** with encode (default/compact), decode, token estimate, and error display. `Configuration/Backend/Modules.php`, `ToonModuleController`, `Resources/Private/Templates/ToonModule/Playground.html`, `Configuration/Icons.php`, `locallang_mod.xlf`. README section “Backend module (TOON Playground)”.

### Added (Phase 3 – Batch 1: B + C + E)

- **Global helpers** (B): `toon()`, `toon_decode()`, `toon_compact()`, `toon_readable()`, `toon_decode_lenient()`, `toon_estimate_tokens()` in `Resources/Private/PHP/helpers.php`, loaded via `ext_localconf.php`.
- **Fluid ViewHelpers** (C): `toon:encode`, `toon:decode`, `toon:estimateTokens` (namespace `RRP\T3Toon\ViewHelpers`, registered as `toon`). Options presets for encode: default, compact, readable, tabular; decode supports `as` and `lenient`.
- **ext_localconf.php**: Loads helpers and registers Fluid namespace `toon`.
- **README**: Sections “Global helpers”, “Fluid ViewHelpers”, and “Format and spec (future)” (E) describing TYPO3 format vs. TOON spec and possible future spec-adapter.

### Added (Phase 2)

- **EncodeOptions** (`RRP\T3Toon\Domain\Model\EncodeOptions`): optional per-call encoding settings — `indent`, `delimiter` (comma/tab), `maxPreviewItems`, `escapeStyle`, `minRowsToTabular`. Presets: `default()`, `compact()`, `readable()`, `tabular()`.
- **DecodeOptions** (`RRP\T3Toon\Domain\Model\DecodeOptions`): optional per-call decoding — `coerceScalarTypes`. Presets: `default()`, `lenient()`.
- **ToonHelper::getConfigMerged(array $overrides)** for merging options with extension config.
- **encode/convert/decode** (instance and static) accept optional `?EncodeOptions` / `?DecodeOptions`; null = use extension config.
- **ToonEncoder::toToon($input, ?EncodeOptions $options = null)** and **ToonDecoder::fromToon($toon, ?DecodeOptions $options = null)** with config restore after call (try/finally).
- **Encoder**: configurable indent (spaces per level) and delimiter in tabular rows; config now includes `indent` and `delimiter` (defaults 2 and comma).
- Unit tests: `EncodeOptionsTest`, `DecodeOptionsTest`; options tests in `ToonTest` and `ToonEncoderTest`.
- README: "Configuration options" section with EncodeOptions/DecodeOptions examples.

## [1.3.0] - 2026-01-XX

### Added

- **Static API**: `Toon::encode()`, `Toon::decode()`, `Toon::convert()`, `Toon::estimateTokens()` for convenience without DI.
- **Exception hierarchy**: `RRP\T3Toon\Exception\ToonException` (base) and `ToonDecodeException` with `getLineNumber()` and `getSnippet()` for malformed TOON input.
- **Unit tests**: `ToonEncoderTest`, `ToonDecoderTest`, `ToonDecodeExceptionTest`; round-trip and static API tests in `ToonTest`.

### Changed

- **Decoder**: Malformed lines now throw `ToonDecodeException` with line number and snippet instead of generic `\Exception`.
- **Encoder / Decoder**: Removed unused `array $config = []` constructor parameter; configuration is read from extension settings only.
- **README**: Version badge 1.3.0, Static API and Error handling sections, dual usage example (static vs instance).

### Fixed

- README version badge and license link.

## [1.2.0] and earlier

See extension history and [releases](https://github.com/therohanparmar/t3-toon/releases) for earlier changes.
