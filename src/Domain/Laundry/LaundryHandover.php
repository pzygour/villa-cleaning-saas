<?php

declare(strict_types=1);

namespace App\Domain\Laundry;

use App\Domain\Common\EntityId;
use DateTimeImmutable;

final class LaundryHandover
{
    public function __construct(
        public EntityId $id,
        public EntityId $propertyId,
        public EntityId $locationId,
        public DateTimeImmutable $handoverDate,
        public string $status,
        public string $notes = ''
    ) {
    }
}
