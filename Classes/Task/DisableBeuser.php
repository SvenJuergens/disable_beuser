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

use Psr\EventDispatcher\EventDispatcherInterface;
use SvenJuergens\DisableBeuser\Event\AfterBeUserDisabledEvent;
use SvenJuergens\DisableBeuser\Utility\SendMailUtility;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DisableBeuser
{
    private string $userTable = 'be_users';

    protected array $fields = ['uid', 'username', 'lastlogin', 'realName', 'email', 'crdate'];

    protected bool $sendNotificationEmail = false;

    protected bool $isTestRunner = false;

    protected int $timestamp;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws \Doctrine\DBAL\Exception
     * @throws \Exception
     */
    public function run($time, $notificationEmail, $testRunner): bool
    {
        $this->isTestRunner = $testRunner;
        $this->timestamp = $this->convertToTimeStamp($time);
        $this->sendNotificationEmail = !empty($notificationEmail);

        $usersNotLoggedInInTime = $this->getUsersNotLoggedInInTime();
        $usersNeverNotLoggedIn = $this->getUsersNeverNotLoggedIn();

        $disabledUser = array_merge($usersNotLoggedInInTime, $usersNeverNotLoggedIn);

        if ($this->isTestRunner === false) {
            $this->disableTheseUser($disabledUser);
            $this->eventDispatcher->dispatch(
                new AfterBeUserDisabledEvent($disabledUser, $time)
            );
        }
        if ($this->sendNotificationEmail === true) {
            $this->manageMailTransport($notificationEmail, $disabledUser);
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    public function convertToTimeStamp($time): int
    {
        $dateTime = new \DateTime();
        return $dateTime->modify('-' . $time)->getTimestamp();
    }

    protected function disableTheseUser($disableUser): void
    {
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('be_users');

        $queryBuilder
            ->update($this->getUserTable())
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter(
                        array_column($disableUser, 'uid'),
                        Connection::PARAM_INT_ARRAY
                    )
                )
            )
            ->set('disable', '1')
            ->executeStatement();
    }

    /**
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function manageMailTransport($notificationEmail, $disabledUser): bool
    {
        $returnValue = false;
        if ($this->sendNotificationEmail === false || empty($disabledUser)) {
            return true;
        }

        $emails = GeneralUtility::trimExplode(';', $notificationEmail, true);

        foreach ($emails as $email) {
            $returnValue = SendMailUtility::sendEmail($email, $disabledUser, $this->isTestRunner);
            if ($returnValue === false) {
                break;
            }
        }
        return $returnValue;
    }

    /**
     * Return all non-admin users with a non-zero lastlogin older than the
     * configured threshold, excluding `_cli_*` accounts and accounts marked
     * with `donotdisable`.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getUsersNotLoggedInInTime(): array
    {
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable($this->getUserTable());
        return $queryBuilder
            ->select(...$this->fields)
            ->from($this->getUserTable())
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('admin', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('donotdisable', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->lte('lastlogin', $queryBuilder->createNamedParameter($this->timestamp, Connection::PARAM_INT)),
                    $queryBuilder->expr()->neq('lastlogin', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->notLike('username', $queryBuilder->createNamedParameter('_cli_%'))
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Return all non-admin users that never logged in (lastlogin = 0) and
     * whose creation date is older than the configured threshold,
     * excluding `_cli_*` accounts and accounts marked with `donotdisable`.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getUsersNeverNotLoggedIn(): array
    {
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable($this->getUserTable());
        return $queryBuilder
            ->select(...$this->fields)
            ->from($this->getUserTable())
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('admin', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('lastlogin', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('donotdisable', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->lte('crdate', $queryBuilder->createNamedParameter($this->timestamp, Connection::PARAM_INT)),
                    $queryBuilder->expr()->notLike('username', $queryBuilder->createNamedParameter('_cli_%'))
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function getUserTable(): string
    {
        return $this->userTable;
    }
}
