<?php

namespace SvenJuergens\DisableBeuser\Event;

final class AfterBeUserDisabledEvent
{

    private array $disabledUser;

    private string $time;

    /**
     * @param array $disabledUser
     * @param string $time
     */
    public function __construct(array $disabledUser, string $time)
    {
        $this->disabledUser = $disabledUser;
        $this->time = $time;
    }

    /**
     * @return array
     */
    public function getDisabledUser(): array
    {
        return $this->disabledUser;
    }

    /**
     * @return string
     */
    public function getTime(): string
    {
        return $this->time;
    }
}
