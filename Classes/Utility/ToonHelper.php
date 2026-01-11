<?php

declare(strict_types=1);

namespace RRP\T3Toon\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class ToonHelper
{

    /**
     * @return array
     */
    public static function getConfig(): array
    {
        return [
            'escape_style' => (string)(self::getExtensionSettings()['escape_style'] ?? 'backslash'),
            'min_rows_to_tabular' => (int)(self::getExtensionSettings()['min_rows_to_tabular'] ?? 2),
            'max_preview_items' => (int)(self::getExtensionSettings()['max_preview_items'] ?? 200),
            'coerce_scalar_types' => (bool)(self::getExtensionSettings()['coerce_scalar_types'] ?? 1),
        ];
    }

    /**
     * @return array
     */
    private static function getExtensionSettings(): array
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        return $extensionConfiguration->get('wind_patient_registration');
    }
}