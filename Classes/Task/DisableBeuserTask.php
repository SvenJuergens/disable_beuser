<?php
namespace SvenJuergens\DisableBeuser\Task;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class DisableBeuserTask extends AbstractTask
{
    /**
     * Date/Time Format
     *
     * @var string
     */
    protected $timeOfInactivityToDisable = null;

    protected $notificationEmail = null;

    protected $testRunner = false;

    public function execute()
    {
        $returnValue = false;
        $Logic = GeneralUtility::makeInstance(DisableBeuser::class);
        $returnValue = $Logic->run(
            $this->getTimeOfInactivityToDisable(),
            $this->getNotificationEmail(),
            $this->isTestRunner()
        );
        return $returnValue;
    }

    /**
     * Get the saved Date/Time Format
     *
     * @return string Comma-Separated Lists with uids to Exclude.
     */
    public function getTimeOfInactivityToDisable()
    {
        return $this->timeOfInactivityToDisable;
    }

    /**
     * Sets the Date/Time Format.
     *
     * @param string $timeOfInactivityToDisable Date/Time Format.
     * @return void
     */
    public function setTimeOfInactivityToDisable($timeOfInactivityToDisable)
    {
        $this->timeOfInactivityToDisable = $timeOfInactivityToDisable;
    }

    /**
     * Get E-Mail Address
     *
     * @return string
     */
    public function getNotificationEmail()
    {
        return $this->notificationEmail;
    }

    /**
     * Set E-Mail Address
     *
     * @param string $email E-Mail Address
     * @return void
     */
    public function setNotificationEmail($email)
    {
        $this->notificationEmail = $email;
    }

    /**
     * @return bool
     */
    public function isTestRunner()
    {
        return $this->testRunner;
    }

    /**
     * @param bool $testRunner
     */
    public function setTestRunner($testRunner)
    {
        $this->testRunner = (bool)$testRunner;
    }
}
