<?php

namespace SvenJuergens\DisableBeuser\Event;

use TYPO3\CMS\Core\Mail\MailMessage;

final readonly class AfterMailsAreSentEvent
{
    public function __construct(
        private MailMessage $mailer,
        private array $disabledUser,
    ) {}

    public function getMailer(): MailMessage
    {
        return $this->mailer;
    }

    public function getDisabledUser(): array
    {
        return $this->disabledUser;
    }
}
