<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class GenerateCleaningEventsDTO
{
    public function __construct(
        public string $propertyId,
        public string $fromDate,
        public string $toDate
    ) {
    }
}
