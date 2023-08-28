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
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * Original TASK taken from EXT:reports
 */
class DisableBeuserAdditionalFields extends AbstractAdditionalFieldProvider
{
    /**
     * Field Name.
     *
     * @var array
     */
    protected array $fieldNames = [
        'time' => 'disablebeuser_timeOfInactivityToDisable',
        'email' => 'disablebeuser_email',
        'testrunner' => 'disablebeuser_testrunner',
    ];

    protected string $languageFile = 'LLL:EXT:disable_beuser/Resources/Private/Language/locallang.xlf:';

    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo Values of the fields from the add/edit task form
     * @param DisableBeuserTask $task The task object being edited. Null when adding a task!
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return array A two-dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();

        if ($currentSchedulerModuleAction->equals(Action::EDIT)) {
            $taskInfo[$this->fieldNames['time']] = $task->getTimeOfInactivityToDisable();
            $taskInfo[$this->fieldNames['email']] = $task->getNotificationEmail();
            $taskInfo[$this->fieldNames['testrunner']] = $task->isTestRunner();
            $checked = $task->isTestRunner() === true ? 'checked="checked" ' : '';
        } else {
            $checked = '';
        }

        $additionalFields = [];

        $additionalFields[$this->fieldNames['testrunner']] = [
            'code' => '<input type="checkbox" name="tx_scheduler[' . $this->fieldNames['testrunner'] . ']" ' . $checked . '  />',
            'label' => $GLOBALS['LANG']->sL($this->languageFile . 'scheduler.fieldLabelTestRunner'),
            'cshKey' => '_MOD_txdisablebeuser',
            'cshLabel' => $this->fieldNames['testrunner']
        ];

        $placeHolderText = $GLOBALS['LANG']->sL($this->languageFile . 'scheduler.placeholderText');
        $additionalFields[$this->fieldNames['time']] = [
            'code' => '<input type="text" class="form-control" placeholder="' . $placeHolderText . '" name="tx_scheduler[' . $this->fieldNames['time'] . ']" value="' . ($taskInfo[$this->fieldNames['time']] ?? '') . '" />',
            'label' => $GLOBALS['LANG']->sL($this->languageFile . 'scheduler.fieldLabel'),
            'cshKey' => '_MOD_txdisablebeuser',
            'cshLabel' => $this->fieldNames['time']
        ];

        $additionalFields[$this->fieldNames['email']] = [
            'code' => '<input type="text" class="form-control" placeholder="test@example.org; test@example.com" name="tx_scheduler[' . $this->fieldNames['email'] . ']" value="' . ($taskInfo[$this->fieldNames['email']] ?? '') . '" />',
            'label' => $GLOBALS['LANG']->sL($this->languageFile . 'scheduler.fieldLabelEmail'),
            'cshKey' => '_MOD_txdisablebeuser',
            'cshLabel' => $this->fieldNames['email']
        ];
        return $additionalFields;
    }

    /**
     * Validates the additional fields' values
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        $validInput = true;
        if (empty($submittedData[$this->fieldNames['time']])) {
            $this->addMessage(
                $this->getLanguageService()->sL($this->languageFile . 'error.empty'),
                AbstractMessage::ERROR
            );
            return false;
        }

        try {
            $date = new \DateTime($submittedData[$this->fieldNames['time']]);
        } catch (\Exception $e) {
            $this->addMessage(
                $this->getLanguageService()->sL($this->languageFile . 'error.wrongFormat'),
                AbstractMessage::ERROR
            );
            return false;
        }

        if (!empty($submittedData[$this->fieldNames['email']])) {
            $emails = GeneralUtility::trimExplode(';', $submittedData[$this->fieldNames['email']], true);

            foreach ($emails as $key => $email) {
                if (!GeneralUtility::validEmail($email)) {
                    $this->addMessage(
                        $this->getLanguageService()->sL($this->languageFile . 'error.wrongEmail'),
                        AbstractMessage::ERROR
                    );
                    return false;
                    break;
                }
            }
        }

        return $this->validateTestRunner($submittedData);
    }

    public function validateTestRunner($submittedData): bool
    {
        $validData = false;
        if (!isset($submittedData['disablebeuser_testrunner'])) {
            $validData = true;
        } elseif ($submittedData['disablebeuser_testrunner'] === 'on') {
            $validData = true;
        }
        return $validData;
    }

    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param AbstractTask $task Reference to the scheduler backend module
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
    {
        if (!$task instanceof DisableBeuserTask) {
            throw new \InvalidArgumentException(
                'Expected a task of type ' . DisableBeuserTask::class . ', but got ' . get_class($task),
                1295012802
            );
        }
        $task->setTimeOfInactivityToDisable(htmlspecialchars($submittedData[$this->fieldNames['time']]));
        $task->setNotificationEmail($submittedData[$this->fieldNames['email']]);
        $task->setTestRunner($submittedData[$this->fieldNames['testrunner']] ?? false);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
