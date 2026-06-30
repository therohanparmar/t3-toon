<?php

use RRP\T3Toon\Controller\ToonLogsModuleController;
use RRP\T3Toon\Controller\ToonModuleController;

return [
    // Single backend module under "Tools". The Logs screen is a sub-route of
    // this module (identifier "tools_t3toon.logs"), reached via in-page links
    // rather than its own menu entry. This sub-route mechanism is identical on
    // TYPO3 v12, v13 and v14.
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
            // GET = list, POST = delete (dispatched inside handleRequest()).
            // Linked as route "tools_t3toon.logs".
            'logs' => [
                'target' => ToonLogsModuleController::class . '::handleRequest',
            ],
        ],
    ],
];
