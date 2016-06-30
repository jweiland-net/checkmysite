<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

// hook is called at init FE-object
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'][] = function() {
    /** @var \JWeiland\Checkmysite\Checker\IndexPhpChecker $indexPhpChecker */
    $indexPhpChecker = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JWeiland\\Checkmysite\\Checker\\IndexPhpChecker');
    $indexPhpChecker->checkIndexPhp();
    unset($indexPhpChecker);
};
