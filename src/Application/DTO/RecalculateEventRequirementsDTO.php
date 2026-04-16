<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class RecalculateEventRequirementsDTO
{
    public function __construct(public string $eventId)
    {
    }
}
