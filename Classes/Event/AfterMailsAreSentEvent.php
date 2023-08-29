<?php

namespace SvenJuergens\DisableBeuser\Event;

use TYPO3\CMS\Core\Mail\MailMessage;

class AfterMailsAreSentEvent
{
    private MailMessage $mailer;
    private array $disabledUser;


    /**
     * @param MailMessage $mailer
     * @param array $disabledUser
     */
    public function __construct(MailMessage $mailer, array $disabledUser)
    {
        $this->mailer = $mailer;
        $this->disabledUser = $disabledUser;
    }

    /**
     * @return MailMessage
     */
    public function getMailer(): MailMessage
    {
        return $this->mailer;
    }

    /**
     * @return array
     */
    public function getDisabledUser(): array
    {
        return $this->disabledUser;
    }
}
