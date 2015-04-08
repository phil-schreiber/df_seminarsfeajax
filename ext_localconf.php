<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
$TYPO3_CONF_VARS['FE']['eID_include']['tx_seminarsfeajax_actionController'] = 'EXT:df_seminarsfeajax/class.tx_seminarsfeajax_ajaxcontroller.php';
t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_dfseminarsfeajax_pi1.php', '_pi1', 'list_type', 1);
?>