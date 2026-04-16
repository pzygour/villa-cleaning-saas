<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class CreateRoomDTO
{
    public function __construct(
        public string $propertyId,
        public string $name,
        public string $roomType,
        public int $sortOrder = 0,
        public bool $isActive = true
    ) {
    }
}
