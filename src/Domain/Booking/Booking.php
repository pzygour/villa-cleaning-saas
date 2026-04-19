<?php

declare(strict_types=1);

namespace App\Domain\Booking;

use App\Domain\Common\EntityId;
use DateTimeImmutable;

final class Booking
{
    public function __construct(
        public EntityId $id,
        public EntityId $propertyId,
        public ?string $reference,
        public DateTimeImmutable $arrivalDate,
        public DateTimeImmutable $departureDate,
        public int $guestCount,
        public string $notes = ''
    ) {
    }
}
