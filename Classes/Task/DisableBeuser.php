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

use \TYPO3\CMS\Backend\Utility\BackendUtility;

class DisableBeuser{

	public function run( $time, $checkNeverLoggedInUser = FALSE ){
		$timestamp = $this->convertToTimeStamp( $time );

		// update alle user
		// welche NICHT Administratoren sind
		// und einen lastlogin kleiner/gleich $timestamp haben
		// und lastlogin NICHT 0 ist -> die haben sich noch nicht eingeloggt
		// und nicht mit '_cli' beginnen
		$normalUser = ' admin=0
						AND lastLogin <=' . (int)$timestamp
					. ' AND lastLogin!=0'
					. ' AND username NOT LIKE "_cli_%"'
					. BackendUtility::deleteClause( 'be_users' ) . BackendUtility::BEenableFields( 'be_users' );

		$this->disableUser($normalUser);

		// update alle user
		// welche NICHT Administratoren sind
		// und einen lastlogin GLEICH 0 haben -> die haben sich noch nicht eingeloggt
		// UND ein Erstellungsdatum kleiner/gleich $timestamp haben
		// und nicht mit '_cli' beginnen
		$userNeverLoggedIn = ' 	admin=0
								AND lastLogin = 0'
							. ' AND crdate <=' . (int)$timestamp
							. ' AND username NOT LIKE "_cli_%"'
							. BackendUtility::deleteClause( 'be_users' ) . BackendUtility::BEenableFields( 'be_users' );

		$this->disableUser($userNeverLoggedIn);

		return TRUE;
	}

	public function convertToTimeStamp( $time ){
		 $dateTime = new \DateTime();
		 return $dateTime->modify('-' . $time . 'month')->getTimeStamp();
	}

	public function disableUser( $where ){

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'be_users',
			$where,
			array('disable' => '1')
		);
	}
}
