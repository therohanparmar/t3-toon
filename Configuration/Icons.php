<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

$isV14OrHigher = (new Typo3Version())->getMajorVersion() >= 14;

return [
    'extension-rrp-t3toon' => [
        'provider' => SvgIconProvider::class,
        'source' => $isV14OrHigher
            ? 'EXT:rrp_t3toon/Resources/Public/Icons/ExtensionV14.svg'
            : 'EXT:rrp_t3toon/Resources/Public/Icons/Extension.svg',
    ],
    'module-rrp-t3toon-logs' => [
        'provider' => SvgIconProvider::class,
        'source' => $isV14OrHigher
            ? 'EXT:rrp_t3toon/Resources/Public/Icons/ModuleLogsV14.svg'
            : 'EXT:rrp_t3toon/Resources/Public/Icons/ModuleLogs.svg',
    ],
];
