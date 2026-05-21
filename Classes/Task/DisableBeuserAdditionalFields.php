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

namespace SvenJuergens\DisableBeuser\Task;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\SchedulerManagementAction;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Original TASK taken from EXT:reports
 */
class DisableBeuserAdditionalFields extends AbstractAdditionalFieldProvider
{
    protected array $fieldNames = [
        'time' => 'disablebeuser_timeOfInactivityToDisable',
        'email' => 'disablebeuser_email',
        'testrunner' => 'disablebeuser_testrunner',
    ];

    protected string $languageFile = 'LLL:EXT:disable_beuser/Resources/Private/Language/locallang.xlf:';

    /**
     * @param DisableBeuserTask|null $task null when adding a new task
     * @return array<string, array{code: string, label: string, cshKey: string, cshLabel: string}>
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {

        if ($schedulerModule->getCurrentAction() === SchedulerManagementAction::EDIT) {
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
            'cshLabel' => $this->fieldNames['testrunner'],
        ];

        $placeHolderText = $GLOBALS['LANG']->sL($this->languageFile . 'scheduler.placeholderText');
        $additionalFields[$this->fieldNames['time']] = [
            'code' => '<input type="text" class="form-control" placeholder="' . $placeHolderText . '" name="tx_scheduler[' . $this->fieldNames['time'] . ']" value="' . ($taskInfo[$this->fieldNames['time']] ?? '') . '" />',
            'label' => $GLOBALS['LANG']->sL($this->languageFile . 'scheduler.fieldLabel'),
            'cshKey' => '_MOD_txdisablebeuser',
            'cshLabel' => $this->fieldNames['time'],
        ];

        $additionalFields[$this->fieldNames['email']] = [
            'code' => '<input type="text" class="form-control" placeholder="test@example.org; test@example.com" name="tx_scheduler[' . $this->fieldNames['email'] . ']" value="' . ($taskInfo[$this->fieldNames['email']] ?? '') . '" />',
            'label' => $GLOBALS['LANG']->sL($this->languageFile . 'scheduler.fieldLabelEmail'),
            'cshKey' => '_MOD_txdisablebeuser',
            'cshLabel' => $this->fieldNames['email'],
        ];
        return $additionalFields;
    }

    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        if (empty($submittedData[$this->fieldNames['time']])) {
            $this->addMessage(
                $this->getLanguageService()->sL($this->languageFile . 'error.empty'),
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR
            );
            return false;
        }

        try {
            $date = new \DateTime($submittedData[$this->fieldNames['time']]);
        } catch (\Exception $e) {
            $this->addMessage(
                $this->getLanguageService()->sL($this->languageFile . 'error.wrongFormat'),
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR
            );
            return false;
        }

        if (!empty($submittedData[$this->fieldNames['email']])) {
            $emails = GeneralUtility::trimExplode(';', $submittedData[$this->fieldNames['email']], true);

            foreach ($emails as $email) {
                if (!GeneralUtility::validEmail($email)) {
                    $this->addMessage(
                        $this->getLanguageService()->sL($this->languageFile . 'error.wrongEmail'),
                        \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR
                    );
                    return false;
                }
            }
        }

        return $this->validateTestRunner($submittedData);
    }

    public function validateTestRunner($submittedData): bool
    {
        return !isset($submittedData['disablebeuser_testrunner'])
            || $submittedData['disablebeuser_testrunner'] === 'on';
    }

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

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
