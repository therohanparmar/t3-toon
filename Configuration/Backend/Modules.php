<?php

use RRP\T3Toon\Controller\ToonLogsModuleController;
use RRP\T3Toon\Controller\ToonModuleController;

return [
    'tools_t3toon' => [
        'parent' => 'tools',
        'position' => ['after' => 'extensionmanager'],
        'access' => 'user',
        'path' => '/module/tools/t3toon',
        'iconIdentifier' => 'extension-rrp-t3toon',
        'labels' => 'LLL:EXT:rrp_t3toon/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'RrpT3toon',
        'routes' => [
            '_default' => [
                'target' => ToonModuleController::class . '::playgroundAction',
            ],
        ],
    ],
    'tools_t3toon_logs' => [
        'parent' => 'tools',
        'position' => ['after' => 'tools_t3toon'],
        'access' => 'user',
        'path' => '/module/tools/t3toon-logs',
        'iconIdentifier' => 'module-rrp-t3toon-logs',
        'labels' => [
            'title' => 'LLL:EXT:rrp_t3toon/Resources/Private/Language/locallang_mod.xlf:logs.mlang_tabs_tab',
        ],
        'extensionName' => 'RrpT3toon',
        'routes' => [
            '_default' => [
                'target' => ToonLogsModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
