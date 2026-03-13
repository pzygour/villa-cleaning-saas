<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\BookingRepositoryInterface;
use App\Application\DTO\CreateBookingDTO;
use App\Application\Validators\BookingValidator;

final class BookingService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingValidator $validator
    ) {
    }

    public function create(CreateBookingDTO $dto): void
    {
        $this->validator->validate($dto);
        // Mapping and persistence implemented in the next phase.
    }
}
