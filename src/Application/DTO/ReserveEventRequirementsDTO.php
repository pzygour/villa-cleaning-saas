<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class ReserveEventRequirementsDTO
{
    public function __construct(
        public string $eventId,
        public string $locationId,
        public ?string $createdByUserId = null
    ) {
    }
}
