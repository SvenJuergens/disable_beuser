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

use \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use \TYPO3\CMS\Core\Messaging\FlashMessage;
use \TYPO3\CMS\Scheduler\Task\AbstractTask;


/**
 * Original TASK taken from EXT:reports
 *
 */
class DisableBeuserAdditionalFields implements AdditionalFieldProviderInterface{

	/**
	 * Field Name.
	 *
	 * @var string
	 */
	protected $fieldName = 'disablebeuser_timeOfInactivityToDisable';

	/**
	 * Gets additional fields to render in the form to add/edit a task
	 *
	 * @param array $taskInfo Values of the fields from the add/edit task form
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task The task object being edited. Null when adding a task!
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
	 */
	public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule) {

		if ($schedulerModule->CMD == 'edit') {
			$taskInfo[$this->fieldName] = $task->getTimeOfInactivityToDisable();
		}

		$additionalFields = array();
		$placeHolderText = $GLOBALS['LANG']->sL('LLL:EXT:disable_beuser/locallang.xml:scheduler.placeholderText');
		$additionalFields[ $this->fieldName ] = array(
			'code'     => '<input type="text" placeholder="' . $placeHolderText . '" name="tx_scheduler[' . $this->fieldName . ']" value="' . $taskInfo[$this->fieldName] . '" />',
			'label'    => $GLOBALS['LANG']->sL('LLL:EXT:disable_beuser/locallang.xml:scheduler.fieldLabel'),
			'cshKey'   => '_MOD_txdisablebeuser',
			'cshLabel' => $this->fieldName
		);

		return $additionalFields;
	}


	/**
	 * Validates the additional fields' values
	 *
	 * @param array $submittedData An array containing the data submitted by the add/edit task form
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule) {
		$validInput = TRUE;
		if( empty($submittedData[$this->fieldName]) ){
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:disable_beuser/locallang.xml:error.empty'),
				FlashMessage::ERROR
			);
			$validInput = FALSE;
		}

		try {
			$date = new \DateTime( $submittedData[$this->fieldName] );
		} catch ( \Exception $e) {
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:disable_beuser/locallang.xml:error.wrongFormat'),
				FlashMessage::ERROR);
			$validInput = FALSE;
		}

		return $validInput;
	}

	/**
	 * Takes care of saving the additional fields' values in the task's object
	 *
	 * @param array $submittedData An array containing the data submitted by the add/edit task form
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the scheduler backend module
	 * @return void
	 */
	public function saveAdditionalFields(array $submittedData, AbstractTask $task) {
		if (!$task instanceof DisableBeuserTask) {
			throw new \InvalidArgumentException('Expected a task of type SvenJuergens\\DisableBeuser\\Task\\DisableBeuserTask, but got ' . get_class($task), 1295012802);
		}
		$task->setTimeOfInactivityToDisable( htmlspecialchars($submittedData[$this->fieldName]) );
	}
}
