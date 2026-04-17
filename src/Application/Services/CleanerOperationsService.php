<?php

declare(strict_types=1);

namespace App\Application\Services;

final class CleanerOperationsService
{
    public function __construct(private CleaningScheduleQueryService $schedule)
    {
    }

    public function assignedEvents(string $cleanerId, string $fromDate, string $toDate): array
    {
        return $this->schedule->byCleaner($cleanerId, $fromDate, $toDate);
    }
}
