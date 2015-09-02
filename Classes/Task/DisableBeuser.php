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

	public function run( $months ){
		$timestamp = $this->convertToTimeStamp( $months );

		//update alle user
		//welche einen lastlogin kleiner/gleich $timestamp haben
		$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = 1;
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'be_users',
			'admin=0 AND lastLogin <=' . (int)$timestamp . BackendUtility::deleteClause( 'be_users' ) . BackendUtility::BEenableFields( 'be_users' ),
			array('disable' => '1')
		);
		return TRUE;
	}

	public function convertToTimeStamp( $months ){
		 $dateTime = new \DateTime();
		 return $dateTime->modify('-' . $months . 'month')->getTimeStamp();
	}
}
