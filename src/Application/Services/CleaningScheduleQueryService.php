<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\CleaningScheduleQueryRepositoryInterface;

final class CleaningScheduleQueryService
{
    public function __construct(private readonly CleaningScheduleQueryRepositoryInterface $schedule)
    {
    }

    public function byProperty(string $propertyId, string $fromDate, string $toDate): array
    {
        return $this->schedule->eventsByPropertyAndRange($propertyId, $fromDate, $toDate);
    }

    public function allProperties(string $fromDate, string $toDate): array
    {
        return $this->schedule->eventsAllPropertiesByRange($fromDate, $toDate);
    }

    public function byCleaner(string $userId, string $fromDate, string $toDate): array
    {
        return $this->schedule->eventsByCleanerAndRange($userId, $fromDate, $toDate);
    }

    public function byDay(string $date): array
    {
        return $this->schedule->eventsByDay($date);
    }
}
