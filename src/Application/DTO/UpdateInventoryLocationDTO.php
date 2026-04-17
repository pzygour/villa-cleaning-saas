<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class UpdateInventoryLocationDTO
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public string $locationType,
        public ?string $propertyId = null,
        public bool $isActive = true
    ) {
    }
}
