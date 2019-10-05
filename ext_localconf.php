<?php
defined('TYPO3_MODE') or die();

/**
 * Registering class to scheduler
 */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][SvenJuergens\DisableBeuser\Task\DisableBeuserTask::class] = [
    'extension' => 'disable_beuser',
    'title' => 'Disable Beuser',
    'description' => 'Disable Beuser after inactive time',
    'additionalFields' => SvenJuergens\DisableBeuser\Task\DisableBeuserAdditionalFields::class
];

if (version_compare(TYPO3_version, '9.5.0', '<')) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][SvenJuergens\DisableBeuser\Task\DisableBeuserTask::class] = [
        'extension' => 'disable_beuser',
        'title' => 'Disable Beuser',
        'description' => 'Disable Beuser after inactive time',
        'additionalFields' => SvenJuergens\DisableBeuser\Task\DisableBeuserAdditionalFieldsv8::class
    ];
}