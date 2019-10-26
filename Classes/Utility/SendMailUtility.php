<?php
namespace SvenJuergens\DisableBeuser\Utility;

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
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

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
    public static function sendEmail($notificationEmail, $disabledUser, $isTestRunner)
    {
        $success = false;
        if (!GeneralUtility::validEmail($notificationEmail)) {
            return $success;
        }

        $mailBody = self::getMailBody($disabledUser, $isTestRunner);

        $setFrom = MailUtility::getSystemFromAddress();
        // Prepare mailer and send the mail
        $mailer = GeneralUtility::makeInstance(MailMessage::class);
        $mailer->setFrom($setFrom)
                ->setSubject('SCHEDULER-Task DisableBeuser:' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']))
                ->setBody($mailBody, 'text/html')
                ->setTo($notificationEmail);
        $mailsSend = $mailer->send();
        $success = $mailsSend > 0;
        return $success;
    }

    /**
     * @param $disabledUser
     * @param $isTestRunner
     * @return mixed
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public static function getMailBody($disabledUser, $isTestRunner)
    {
        if (class_exists(ExtensionConfiguration::class)) {
            $extensionConfig = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('disable_beuser');
        } else {
            //@extensionScannerIgnoreLine
            $extensionConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['disable_beuser']);
        }

        if (empty($extensionConfig)) {
            $extensionConfig['templatePath'] = 'EXT:disable_beuser/Resources/Private/Templates/emailTemplate.html';
        }

        $templateFile = GeneralUtility::getFileAbsFileName($extensionConfig['templatePath']);
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename($templateFile);
        $view->assignMultiple([
            'disabledUser' => $disabledUser,
            'isTestRunner' => $isTestRunner
        ]);
        return $view->render();
    }
}
