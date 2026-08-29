<?php

declare(strict_types=1);

namespace RRP\T3Toon\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class ToonHelper
{

    /**
     * Default configuration from extension settings.
     *
     * @return array<string, mixed>
     */
    public static function getConfig(): array
    {
        $settings = self::getExtensionSettings();
        // flatten_depth: -1 (or empty) means "unbounded" -> null for the Spec encoder.
        $flatten = $settings['flatten_depth'] ?? '';
        $flattenDepth = ($flatten === '' || (int) $flatten < 0) ? null : (int) $flatten;

        return [
            'enabled' => (bool) ($settings['enabled'] ?? true),
            'indent' => max(1, (int) ($settings['indent'] ?? 2)),
            'delimiter' => self::normalizeDelimiter((string) ($settings['delimiter'] ?? ',')),
            'key_folding' => ($settings['key_folding'] ?? 'off') === 'safe' ? 'safe' : 'off',
            'flatten_depth' => $flattenDepth,
            'strict' => (bool) ($settings['strict'] ?? true),
            'expand_paths' => ($settings['expand_paths'] ?? 'off') === 'safe' ? 'safe' : 'off',
            'show_default_example' => (bool) ($settings['show_default_example'] ?? true),
            'json_baseline' => self::normalizeJsonBaseline((string) ($settings['json_baseline'] ?? 'minified')),
        ];
    }

    /**
     * Whether TOON encoding is globally enabled.
     */
    public static function isEnabled(): bool
    {
        return self::getConfig()['enabled'];
    }

    /**
     * Map a configured delimiter token to the literal character used by the Spec engine.
     * Accepts the named tokens "comma"/"tab"/"pipe" as well as the literal characters.
     */
    private static function normalizeDelimiter(string $value): string
    {
        return match ($value) {
            'tab', "\t", '\t' => "\t",
            'pipe', '|' => '|',
            default => ',',
        };
    }

    /**
     * Restrict the JSON baseline setting to the supported tokens.
     */
    private static function normalizeJsonBaseline(string $value): string
    {
        return in_array($value, ['minified', 'pretty2', 'pretty4', 'tabs'], true) ? $value : 'minified';
    }

    /**
     * Configuration merged with optional overrides (e.g. from EncodeOptions/DecodeOptions).
     *
     * @param array<string, mixed> $overrides Keys matching getConfig() override extension config
     * @return array<string, mixed>
     */
    public static function getConfigMerged(array $overrides): array
    {
        return array_merge(self::getConfig(), $overrides);
    }

    /**
     * Retrieves extension configuration settings for rrp_t3toon.
     *
     * @return array Extension configuration array
     */
    private static function getExtensionSettings(): array
    {
        try {
            $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
            return $extensionConfiguration->get('rrp_t3toon') ?: [];
        } catch (\Throwable $e) {
            // Return empty array if configuration cannot be loaded
            return [];
        }
    }
}