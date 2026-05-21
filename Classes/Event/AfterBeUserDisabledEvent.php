<?php

declare(strict_types=1);

namespace SvenJuergens\DisableBeuser\Event;

final readonly class AfterBeUserDisabledEvent
{
    public function __construct(
        private array $disabledUser,
        private string $time,
    ) {}

    public function getDisabledUser(): array
    {
        return $this->disabledUser;
    }

    public function getTime(): string
    {
        return $this->time;
    }
}
