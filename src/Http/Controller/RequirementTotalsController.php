<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Services\RequirementTotalsQueryService;

final class RequirementTotalsController
{
    public function __construct(private readonly RequirementTotalsQueryService $totals)
    {
    }

    public function byEvents(string $eventIdsCsv): array
    {
        $eventIds = array_values(array_filter(array_map('trim', explode(',', $eventIdsCsv))));

        return ['data' => $this->totals->byEvents($eventIds)];
    }

    public function byDay(?string $propertyId, string $date): array
    {
        return ['data' => $this->totals->byDay($propertyId, $date)];
    }

    public function byPropertyRange(string $propertyId, string $fromDate, string $toDate): array
    {
        return ['data' => $this->totals->byPropertyRange($propertyId, $fromDate, $toDate)];
    }
}
