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
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class DisableBeuserTask extends AbstractTask
{

    /**
     * Date/Time Format
     *
     * @var null|string
     */
    protected ?string $timeOfInactivityToDisable = null;

    protected ?string $notificationEmail = null;

    protected bool $testRunner = false;

    /**
     * @return bool
     * @throws Exception|\Doctrine\DBAL\Driver\Exception
     */
    public function execute(): bool
    {
        return GeneralUtility::makeInstance(DisableBeuser::class)->run(
            $this->getTimeOfInactivityToDisable(),
            $this->getNotificationEmail(),
            $this->isTestRunner()
        );
    }

    /**
     * Get the saved Date/Time Format
     *
     * @return string
     */
    public function getTimeOfInactivityToDisable(): string
    {
        return $this->timeOfInactivityToDisable;
    }

    /**
     * Sets the Date/Time Format.
     *
     * @param string $timeOfInactivityToDisable Date/Time Format.
     */
    public function setTimeOfInactivityToDisable(string $timeOfInactivityToDisable): void
    {
        $this->timeOfInactivityToDisable = $timeOfInactivityToDisable;
    }

    /**
     * Get E-Mail Address
     *
     * @return string
     */
    public function getNotificationEmail(): string
    {
        return $this->notificationEmail;
    }

    /**
     * Set E-Mail Address
     *
     * @param string $email E-Mail Address
     */
    public function setNotificationEmail(string $email): void
    {
        $this->notificationEmail = $email;
    }

    /**
     * @return bool
     */
    public function isTestRunner(): bool
    {
        return $this->testRunner;
    }

    /**
     * @param bool $testRunner
     */
    public function setTestRunner(bool $testRunner): void
    {
        $this->testRunner = $testRunner;
    }
}
