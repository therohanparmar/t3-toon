<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'extension-rrp-t3toon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:rrp_t3toon/Resources/Public/Icons/Extension.svg',
    ],
    'module-rrp-t3toon-logs' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:rrp_t3toon/Resources/Public/Icons/ModuleLogs.svg',
    ],
];
