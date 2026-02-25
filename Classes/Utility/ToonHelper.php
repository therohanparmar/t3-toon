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
        return [
            'enabled' => (bool) ($settings['enabled'] ?? true),
            'indent' => (int) ($settings['indent'] ?? 2),
            'delimiter' => (string) ($settings['delimiter'] ?? ','),
            'escape_style' => (string) ($settings['escape_style'] ?? 'backslash'),
            'min_rows_to_tabular' => (int) ($settings['min_rows_to_tabular'] ?? 2),
            'max_preview_items' => (int) ($settings['max_preview_items'] ?? 200),
            'coerce_scalar_types' => (bool) ($settings['coerce_scalar_types'] ?? true),
            'primitive_array_header' => (bool) ($settings['primitive_array_header'] ?? false),
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