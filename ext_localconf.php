<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$GLOBALS['T3_VAR']['ext'][$_EXTKEY]['setup'] = unserialize($_EXTCONF);

// hook is called at init FE-object
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-PostProc'][] = 'EXT:checkmysite/class.hook_fepostproc.php:&tx_hookFePostProc->checkFile';

?>
