<?php
defined('TYPO3_MODE') or die();

/**
 * Add extra field
 */
$temp = [
    'donotdisable' => [
        'displayCond' => 'FIELD:admin:=:0',
        'exclude' => 1,
        'label' => 'LLL:EXT:disable_beuser/Resources/Private/Language/locallang.xlf:beuser.donotdisable',
        'config' => [
            'type' => 'check',
            'default' => 0
        ]
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $temp);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'donotdisable');
