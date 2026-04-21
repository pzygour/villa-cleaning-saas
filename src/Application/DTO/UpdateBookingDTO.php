<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class UpdateBookingDTO
{
    public function __construct(
        public string $id,
        public ?string $reference,
        public string $arrivalDate,
        public string $departureDate,
        public int $guestCount,
        public string $notes = ''
    ) {
    }
}
