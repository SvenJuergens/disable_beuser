<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// Add context sensitive help (csh) for scheduler task
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_txdisablebeuser', 'EXT:' . $_EXTKEY . '/locallang_csh_disablebeuser.xlf');
