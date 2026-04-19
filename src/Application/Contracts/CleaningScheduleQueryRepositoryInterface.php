<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface CleaningScheduleQueryRepositoryInterface
{
    public function eventsByPropertyAndRange(string $propertyId, string $fromDate, string $toDate): array;

    public function eventsAllPropertiesByRange(string $fromDate, string $toDate): array;

    public function eventsByCleanerAndRange(string $userId, string $fromDate, string $toDate): array;

    public function eventsByDay(string $date): array;
}
