<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\BookingRepositoryInterface;
use App\Application\Contracts\CleaningEventRepositoryInterface;

final class CleaningScheduleService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly CleaningEventRepositoryInterface $events
    ) {
    }

    public function generateForPeriod(string $propertyId, \DateTimeImmutable $from, \DateTimeImmutable $to): void
    {
        // Cleaning algorithm (arrival/departure/mid-stay merge rules) will be implemented next phase.
    }
}
