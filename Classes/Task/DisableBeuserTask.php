<?php

declare(strict_types=1);

/*
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

namespace SvenJuergens\DisableBeuser\Task;

use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

class DisableBeuserTask extends AbstractTask
{
    protected ?string $timeOfInactivityToDisable = null;

    protected ?string $notificationEmail = null;

    protected bool $testRunner = false;

    /**
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

    public function getTimeOfInactivityToDisable(): string
    {
        return $this->timeOfInactivityToDisable;
    }

    public function setTimeOfInactivityToDisable(string $timeOfInactivityToDisable): void
    {
        $this->timeOfInactivityToDisable = $timeOfInactivityToDisable;
    }

    public function getNotificationEmail(): string
    {
        return $this->notificationEmail;
    }

    public function setNotificationEmail(string $email): void
    {
        $this->notificationEmail = $email;
    }

    public function isTestRunner(): bool
    {
        return $this->testRunner;
    }

    public function setTestRunner(bool $testRunner): void
    {
        $this->testRunner = $testRunner;
    }
}
