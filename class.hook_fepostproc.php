<?php

require_once(t3lib_extMgm::extPath('checkmysite').'lib/class.checkfile.php');

class tx_hookFePostProc {
	
	public function checkFile($funcRef,$params) {
		$arr_conf = unserialize($params->TYPO3_CONF_VARS['EXT']['extConf']['checkmysite']);
		$obj_checkFile = new CheckFile();
		$obj_checkFile->checkIndexPhp($arr_conf);
		unset($obj_checkFile);
	}
}