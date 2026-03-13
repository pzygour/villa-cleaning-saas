<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Domain\Booking\Booking;
use DateTimeImmutable;

interface BookingRepositoryInterface
{
    public function save(Booking $booking): void;

    /** @return list<Booking> */
    public function forPropertyBetween(string $propertyId, DateTimeImmutable $from, DateTimeImmutable $to): array;
}
