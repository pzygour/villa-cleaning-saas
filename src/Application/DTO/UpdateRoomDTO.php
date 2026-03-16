<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class UpdateRoomDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $roomType,
        public int $sortOrder = 0,
        public bool $isActive = true
    ) {
    }
}
