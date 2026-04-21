<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class CreatePropertyDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public string $locationLabel,
        public string $propertyType,
        public ?string $operationalNotes,
        public bool $isActive = true
    ) {
    }
}
