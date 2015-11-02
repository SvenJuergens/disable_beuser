<?php
defined('TYPO3_MODE') or die();

/**
 * Registering class to scheduler
 */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][SvenJuergens\DisableBeuser\Task\DisableBeuserTask::class] = array(
    'extension' => $_EXTKEY,
    'title' => 'Disable Beuser',
    'description' => 'Disable Beuser after inactive time',
    'additionalFields' => SvenJuergens\DisableBeuser\Task\DisableBeuserAdditionalFields::class
);
