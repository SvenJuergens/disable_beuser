<?php
defined('TYPO3_MODE') or die();

/**
 * Add extra field
 */
$temp = array(
	'donotdisable' => array(
		'displayCond' => 'FIELD:admin:=:0',
		'exclude' => 1,
		'label' => 'LLL:EXT:disable_beuser/locallang.xml:beuser.donotdisable',
		'config' => array(
			'type' => 'check',
			'default' => 0
		)
	),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $temp);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'donotdisable');
