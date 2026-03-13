<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class CreateBookingDTO
{
    public function __construct(
        public string $propertyId,
        public string $reference,
        public string $arrivalDate,
        public string $departureDate,
        public int $guestCount,
        public string $notes = ''
    ) {
    }
}
