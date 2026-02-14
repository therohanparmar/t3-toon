<?php

defined('TYPO3') || die();

(function () {
    // Global helper functions (toon(), toon_decode(), etc.)
    $helpersFile = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('rrp_t3toon', 'Resources/Private/PHP/helpers.php');
    if (file_exists($helpersFile)) {
        require_once $helpersFile;
    }

    // Fluid ViewHelper namespace: use {toon:encode value="{data}"} in templates
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['toon'] = ['RRP\T3Toon\ViewHelpers'];
})();
