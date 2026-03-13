<?php

declare(strict_types=1);

namespace App\Domain\Cleaning;

use App\Domain\Common\EntityId;
use DateTimeImmutable;

final readonly class CleaningEvent
{
    public function __construct(
        public EntityId $id,
        public EntityId $propertyId,
        public ?EntityId $bookingId,
        public DateTimeImmutable $eventDate,
        public CleaningEventType $type,
        public string $status = 'planned'
    ) {
    }
}
