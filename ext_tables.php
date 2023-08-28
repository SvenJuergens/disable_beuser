<?php
defined('TYPO3') or die();

// Add context sensitive help (csh) for scheduler task
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    '_MOD_txdisablebeuser',
    'EXT:disable_beuser/Resources/Private/Language/locallang_csh_disablebeuser.xlf'
);
