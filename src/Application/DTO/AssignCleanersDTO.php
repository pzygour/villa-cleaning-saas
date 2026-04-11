<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class AssignCleanersDTO
{
    /** @param list<string> $userIds */
    public function __construct(public string $cleaningEventId, public array $userIds)
    {
    }
}
