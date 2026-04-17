<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class RecalculateEventRequirementsDTO
{
    public function __construct(public string $eventId)
    {
    }
}
