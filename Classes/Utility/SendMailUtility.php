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

namespace SvenJuergens\DisableBeuser\Utility;

use Psr\EventDispatcher\EventDispatcherInterface;
use SvenJuergens\DisableBeuser\Event\AfterMailsAreSentEvent;
use SvenJuergens\DisableBeuser\Event\BeforeMailsAreSentEvent;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

class SendMailUtility
{
    /**
     * @param $notificationEmail
     * @param $disabledUser
     * @param $isTestRunner
     * @return bool
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public static function sendEmail($notificationEmail, $disabledUser, $isTestRunner): bool
    {
        if (!GeneralUtility::validEmail($notificationEmail)) {
            return false;
        }

        $mailBody = self::getMailBody($disabledUser, $isTestRunner);

        $setFrom = MailUtility::getSystemFromAddress();
        // Prepare mailer and send the mail
        $mailer = GeneralUtility::makeInstance(MailMessage::class);
        $mailer->setFrom($setFrom)
                ->setSubject('SCHEDULER-Task DisableBeuser:' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']))
                ->setTo($notificationEmail)
                ->html($mailBody);
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(
            new BeforeMailsAreSentEvent($mailer, $disabledUser)
        );
        $mailerService = GeneralUtility::makeInstance(MailerInterface::class);
        try {
            $mailerService->send($mailer);
            $mailsSend = true;
        } catch (\Throwable) {
            $mailsSend = false;
        }
        $eventDispatcher->dispatch(
            new AfterMailsAreSentEvent($mailer, $disabledUser)
        );
        return $mailsSend;
    }

    /**
     * @param $disabledUser
     * @param $isTestRunner
     * @return string
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public static function getMailBody($disabledUser, $isTestRunner): string
    {
        $extensionConfig = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('disable_beuser');
        $templatePath = !empty($extensionConfig['templatePath'])
            ? $extensionConfig['templatePath']
            : 'EXT:disable_beuser/Resources/Private/Templates/emailTemplate.html';

        $viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
        $view = $viewFactory->create(new ViewFactoryData(
            templatePathAndFilename: $templatePath,
        ));
        $view->assignMultiple([
            'disabledUser' => $disabledUser,
            'isTestRunner' => $isTestRunner,
        ]);
        return $view->render();
    }
}
