<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class CreateInventoryLocationDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public string $locationType,
        public ?string $propertyId = null,
        public bool $isActive = true
    ) {
    }
}
