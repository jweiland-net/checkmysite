<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

// Hook is called at init FE-object
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'][] = function () {
    $indexPhpChecker = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(JWeiland\Checkmysite\Checker\IndexPhpChecker::class);
    $indexPhpChecker->checkIndexPhp();
    unset($indexPhpChecker);
};
