<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Services\CleanerOperationsService;
use App\Application\Services\CleaningScheduleQueryService;

final class ScheduleQueryController
{
    public function __construct(
        private CleaningScheduleQueryService $schedule,
        private CleanerOperationsService $cleanerOperations
    ) {
    }

    public function byProperty(string $propertyId, string $fromDate, string $toDate): array
    {
        return ['data' => $this->schedule->byProperty($propertyId, $fromDate, $toDate)];
    }

    public function all(string $fromDate, string $toDate): array
    {
        return ['data' => $this->schedule->allProperties($fromDate, $toDate)];
    }

    public function byCleaner(string $userId, string $fromDate, string $toDate): array
    {
        return ['data' => $this->schedule->byCleaner($userId, $fromDate, $toDate)];
    }

    public function byDay(string $date): array
    {
        return ['data' => $this->schedule->byDay($date)];
    }

    public function cleanerOperations(string $userId, string $fromDate, string $toDate): array
    {
        return ['data' => $this->cleanerOperations->assignedEvents($userId, $fromDate, $toDate)];
    }
}
