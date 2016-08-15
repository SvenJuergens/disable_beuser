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

use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Original TASK taken from EXT:reports
 *
 */
class DisableBeuserAdditionalFields implements AdditionalFieldProviderInterface
{
    /**
     * Field Name.
     *
     * @var array
     */
    protected $fieldNames = array(
        'time' => 'disablebeuser_timeOfInactivityToDisable',
        'email' => 'disablebeuser_email',
    );

    protected $languageFile = 'LLL:EXT:disable_beuser/Resources/Private/Language/locallang.xlf:';

    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo Values of the fields from the add/edit task form
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task The task object being edited. Null when adding a task!
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        if ($schedulerModule->CMD == 'edit') {
            $taskInfo[$this->fieldNames['time']] = $task->getTimeOfInactivityToDisable();
            $taskInfo[$this->fieldNames['email']] = $task->getNotificationEmail();
        }

        $additionalFields = array();
        $placeHolderText = $GLOBALS['LANG']->sL($this->languageFile . 'scheduler.placeholderText');
        $additionalFields[$this->fieldNames['time']] = array(
            'code' => '<input type="text" class="form-control" placeholder="' . $placeHolderText . '" name="tx_scheduler[' . $this->fieldNames['time'] . ']" value="' . $taskInfo[$this->fieldNames['time']] . '" />',
            'label' => $GLOBALS['LANG']->sL($this->languageFile . 'scheduler.fieldLabel'),
            'cshKey' => '_MOD_txdisablebeuser',
            'cshLabel' => $this->fieldNames['time']
        );

        $additionalFields[$this->fieldNames['email']] = array(
            'code' => '<input type="text" class="form-control" name="tx_scheduler[' . $this->fieldNames['email'] . ']" value="' . $taskInfo[$this->fieldNames['email']] . '" />',
            'label' => $GLOBALS['LANG']->sL($this->languageFile . 'scheduler.fieldLabelEmail'),
            'cshKey' => '_MOD_txdisablebeuser',
            'cshLabel' => $this->fieldNames['email']
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
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $validInput = true;
        if (empty($submittedData[$this->fieldNames['time']])) {
            $schedulerModule->addMessage(
                $GLOBALS['LANG']->sL($this->languageFile . 'error.empty'),
                FlashMessage::ERROR
            );
            $validInput = false;
        }

        try {
            $date = new \DateTime($submittedData[$this->fieldNames['time']]);
        } catch (\Exception $e) {
            $schedulerModule->addMessage(
                $GLOBALS['LANG']->sL($this->languageFile . 'error.wrongFormat'),
                FlashMessage::ERROR
            );
            $validInput = false;
        }

        if (!empty($submittedData[$this->fieldNames['email']])) {
            $emails = GeneralUtility::trimExplode(';', $submittedData[$this->fieldNames['email']], true);

            foreach ($emails as $key => $email) {

                if (!GeneralUtility::validEmail($email)) {
                    $schedulerModule->addMessage(
                        $GLOBALS['LANG']->sL($this->languageFile . 'error.wrongEmail'),
                        FlashMessage::ERROR
                    );
                    $validInput = false;
                    break;
                }
            }
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
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        if (!$task instanceof DisableBeuserTask) {
            throw new \InvalidArgumentException('Expected a task of type SvenJuergens\\DisableBeuser\\Task\\DisableBeuserTask, but got ' . get_class($task), 1295012802);
        }
        $task->setTimeOfInactivityToDisable(htmlspecialchars($submittedData[$this->fieldNames['time']]));
        $task->setNotificationEmail($submittedData[$this->fieldNames['email']]);
    }
}
