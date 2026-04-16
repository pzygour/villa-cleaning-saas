<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface RequirementSourceRepositoryInterface
{
    public function findEvent(string $eventId): ?array;

    public function listEventsByPropertyAndPeriod(string $propertyId, string $fromDate, string $toDate): array;

    public function roomBedTotalsByProperty(string $propertyId): array;

    public function roomBathroomTotalsByProperty(string $propertyId): array;

    public function bookingGuestCount(string $bookingId): ?int;
}
