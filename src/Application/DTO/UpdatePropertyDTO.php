<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class UpdatePropertyDTO
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public string $locationLabel,
        public string $propertyType,
        public ?string $operationalNotes,
        public bool $isActive = true
    ) {
    }
}
