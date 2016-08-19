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

use SvenJuergens\DisableBeuser\Utility\SendMailUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class DisableBeuser
{
    protected $disabledUser = array();

    protected $sendNotificationEmail = false;

    protected $isTestRunner = false;

    public function run($time, $notificationEmail, $testRunner)
    {
        $this->isTestRunner = $testRunner;
        $returnValue = true;
        $timestamp = $this->convertToTimeStamp($time);
        $this->sendNotificationEmail = !empty($notificationEmail);

        // update alle user
        // welche NICHT Administratoren sind
        // und einen lastlogin kleiner/gleich $timestamp haben
        // und lastlogin NICHT 0 ist -> die haben sich noch nicht eingeloggt
        // und nicht mit '_cli' beginnen
        $normalUser = ' admin=0
                        AND donotdisable=0'
                    . ' AND lastLogin <=' . (int)$timestamp
                    . ' AND lastLogin!=0'
                    . ' AND username NOT LIKE "_cli_%"'
                    . BackendUtility::deleteClause('be_users')
                    . BackendUtility::BEenableFields('be_users');

        $this->disableUser($normalUser);

        // update alle user
        // welche NICHT Administratoren sind
        // und einen lastlogin GLEICH 0 haben -> die haben sich noch nicht eingeloggt
        // UND ein Erstellungsdatum kleiner/gleich $timestamp haben
        // und nicht mit '_cli' beginnen
        $userNeverLoggedIn = '  admin=0
                                AND lastLogin = 0'
                            . ' AND donotdisable=0'
                            . ' AND crdate <=' . (int)$timestamp
                            . ' AND username NOT LIKE "_cli_%"'
                            . BackendUtility::deleteClause('be_users')
                            . BackendUtility::BEenableFields('be_users');

        $this->disableUser($userNeverLoggedIn);
        return $this->manageMailTransport($notificationEmail);
    }


    /**
     * returns a timestamp
     *
     * @param $time
     * @return int
     */
    public function convertToTimeStamp($time)
    {
        $dateTime = new \DateTime();
        return $dateTime->modify('-' . $time)->getTimeStamp();
    }

    /**
     * Updates BeUser
     *
     * @param $where
     */
    public function disableUser($where)
    {
        if ($this->sendNotificationEmail === true) {
            $rows = array();
            $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                'username,lastlogin',
                'be_users',
                $where
            );
            $this->disabledUser = array_merge($this->disabledUser, $rows);
        }

        if($this->isTestRunner === false){
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                'be_users',
                $where,
                array('disable' => '1')
            );
        }
    }

    /**
     * Checks if it's necessary to send a notification Mail
     *
     * @param $notificationEmail E-Mail(s) to inform about User
     * @return bool
     */
    public function manageMailTransport($notificationEmail)
    {
        $returnValue = false;
        if ($this->sendNotificationEmail === false || empty($this->disabledUser)) {
            return true;
        }

        $emails = GeneralUtility::trimExplode(';', $notificationEmail, true);

        foreach ($emails as $key => $email) {
            $returnValue = SendMailUtility::sendEmail($email, $this->disabledUser);
            if ($returnValue === false) {
                break;
            }
        }
        return $returnValue;
    }
}
