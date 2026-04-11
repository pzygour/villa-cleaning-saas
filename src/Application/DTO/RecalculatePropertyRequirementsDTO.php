<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class RecalculatePropertyRequirementsDTO
{
    public function __construct(
        public string $propertyId,
        public string $fromDate,
        public string $toDate
    ) {
    }
}
