<?php
defined('TYPO3') or die();

/**
 * Registering class to scheduler
 */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][SvenJuergens\DisableBeuser\Task\DisableBeuserTask::class] = [
    'extension' => 'disable_beuser',
    'title' => 'Disable Beuser',
    'description' => 'Disable Beuser after inactive time',
    'additionalFields' => SvenJuergens\DisableBeuser\Task\DisableBeuserAdditionalFields::class
];
