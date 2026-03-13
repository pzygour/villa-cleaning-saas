<?php

declare(strict_types=1);

namespace App\Domain\Property;

use App\Domain\Common\EntityId;

final readonly class Room
{
    public function __construct(
        public EntityId $id,
        public EntityId $propertyId,
        public string $name,
        public string $roomType,
        public int $sortOrder
    ) {
    }
}
