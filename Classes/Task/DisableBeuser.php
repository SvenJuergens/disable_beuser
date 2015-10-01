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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class DisableBeuser{

	protected $disabledUser = array();

	public function run( $time, $notificationEmail ){

		$returnValue = TRUE;
		$timestamp = $this->convertToTimeStamp( $time );
		// update alle user
		// welche NICHT Administratoren sind
		// und einen lastlogin kleiner/gleich $timestamp haben
		// und lastlogin NICHT 0 ist -> die haben sich noch nicht eingeloggt
		// und nicht mit '_cli' beginnen
		$normalUser = ' admin=0
						AND donotdisable=0'
					. '	AND lastLogin <=' . (int)$timestamp
					. ' AND lastLogin!=0'
					. ' AND username NOT LIKE "_cli_%"'
					. BackendUtility::deleteClause( 'be_users' )
					. BackendUtility::BEenableFields( 'be_users' );

		$this->disableUser($normalUser, $notificationEmail);

		// update alle user
		// welche NICHT Administratoren sind
		// und einen lastlogin GLEICH 0 haben -> die haben sich noch nicht eingeloggt
		// UND ein Erstellungsdatum kleiner/gleich $timestamp haben
		// und nicht mit '_cli' beginnen
		$userNeverLoggedIn = ' 	admin=0
								AND lastLogin = 0'
							. ' AND donotdisable=0'
							. ' AND crdate <=' . (int)$timestamp
							. ' AND username NOT LIKE "_cli_%"'
							. BackendUtility::deleteClause( 'be_users' )
							. BackendUtility::BEenableFields( 'be_users' );

		$this->disableUser($userNeverLoggedIn, $notificationEmail);

		if(!empty($notificationEmail) && !empty($this->disabledUser)){
			$returnValue = $this->sendEmail( $notificationEmail );
		}
		return $returnValue;
	}

	public function convertToTimeStamp( $time ){
		 $dateTime = new \DateTime();
		 return $dateTime->modify('-' . $time )->getTimeStamp();
	}

	public function disableUser( $where, $notificationEmail ){
		if( !empty($notificationEmail) ){
			$rows = array();
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'username,lastlogin',
				'be_users',
				$where
			);
			$this->disabledUser = array_merge($this->disabledUser, $rows);
		}

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'be_users',
			$where,
			array('disable' => '1')
		);
	}

	public function sendEmail( $notificationEmail ){

		$success = FALSE;
		if( !GeneralUtility::validEmail($notificationEmail)){
			return $success;
		}

		$mailBody = $this->getMailBody();

		// Prepare mailer and send the mail
		try {
			$mailer = GeneralUtility::makeInstance( MailMessage::class );
			$mailer->setFrom( $notificationEmail );
			$mailer->setSubject('SCHEDULER-Task DisableBeuser:' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] );
			$mailer->setBody( $mailBody, 'text/html');
			$mailer->setTo( $notificationEmail );
			$mailsSend = $mailer->send();
			$success = $mailsSend > 0;
		} catch ( Exception $e) {
			throw new Exception( $e->getMessage() );
		}
		return $success;
	}

	public function getMailBody(){

		$extensionConfig = array();
		$extensionConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['disable_beuser']);

		if( empty($extensionConfig) ){
			$extensionConfig['templatePath'] = 'EXT:disable_beuser/Resources/Private/Templates/emailTemplate.html';
		}

		$templateFile = GeneralUtility::getFileAbsFileName( $extensionConfig['templatePath'] );
		$view = GeneralUtility::makeInstance( StandaloneView::class );
		$view->setTemplatePathAndFilename( $templateFile );


		$view->assignMultiple( array(
			'disabledUser' => $this->disabledUser,
		));

		return $view->render();

	}
}
