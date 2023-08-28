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

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use SvenJuergens\DisableBeuser\Event\AfterBeUserDisabledEvent;
use SvenJuergens\DisableBeuser\Utility\SendMailUtility;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DisableBeuser
{
    private string $userTable = 'be_users';

    private EventDispatcherInterface $eventDispatcher;

    /**
     * Fields to select
     * @var array
     */
    protected array $fields = ['uid', 'username', 'lastlogin', 'realName', 'email', 'crdate'];

    /**
     * sendNotificationEmail
     *
     * @var bool
     */
    protected bool $sendNotificationEmail = false;

    /**
     * isTestRunner
     *
     * @var bool
     */
    protected bool $isTestRunner = false;
    /**
     * @var int
     */
    protected int $timestamp;

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param $time
     * @param $notificationEmail
     * @param $testRunner
     * @return bool
     * @throws Exception
     * @throws \Exception
     * @throws \Doctrine\DBAL\Driver\Exception
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
     * returns a timestamp
     *
     * @param $time
     * @return int
     * @throws \Exception
     */
    public function convertToTimeStamp($time): int
    {
        $dateTime = new \DateTime();
        return $dateTime->modify('-' . $time)->getTimestamp();
    }

    /**
     * @throws DBALException
     */
    protected function disableTheseUser($disableUser): void
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
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
     * Checks if it's necessary to send a notification Mail
     *
     * @param $notificationEmail
     * @param $disabledUser
     * @return bool
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

        foreach ($emails as $key => $email) {
            $returnValue = SendMailUtility::sendEmail($email, $disabledUser, $this->isTestRunner);
            if ($returnValue === false) {
                break;
            }
        }
        return $returnValue;
    }

    /**
     * get alle user
     * welche NICHT Administratoren sind
     * und einen lastlogin kleiner/gleich $timestamp haben
     * und lastlogin NICHT 0 ist → die haben sich noch nicht eingeloggt
     * und nicht mit '_cli' beginnen
     *
     * @return array
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function getUsersNotLoggedInInTime(): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->getUserTable());
        return $queryBuilder
            ->select(...$this->fields)
            ->from($this->getUserTable())
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('admin', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('donotdisable', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->lte('lastlogin', $queryBuilder->createNamedParameter($this->timestamp, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->neq('lastlogin', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->notLike('username', $queryBuilder->createNamedParameter('_cli_%'))
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * get alle user
     * welche NICHT Administratoren sind
     * und einen lastlogin GLEICH 0 haben -> die haben sich noch nicht eingeloggt
     * UND ein Erstellungsdatum kleiner/gleich $timestamp haben
     * und nicht mit '_cli' beginnen
     *
     * @return array
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function getUsersNeverNotLoggedIn(): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->getUserTable());
        return $queryBuilder
            ->select(...$this->fields)
            ->from($this->getUserTable())
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('admin', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('lastlogin', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->eq('donotdisable', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->lte('crdate', $queryBuilder->createNamedParameter($this->timestamp, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->notLike('username', $queryBuilder->createNamedParameter('_cli_%'))
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return string
     */
    public function getUserTable(): string
    {
        return $this->userTable;
    }
}
