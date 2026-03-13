<?php

declare(strict_types=1);

namespace App\Domain\Property;

use App\Domain\Common\EntityId;

final readonly class Property
{
    public function __construct(
        public EntityId $id,
        public string $code,
        public string $name,
        public string $location,
        public PropertyType $type,
        public string $operationalNotes = '',
        public bool $isActive = true
    ) {
    }
}
