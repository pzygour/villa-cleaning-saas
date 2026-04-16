<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\RequirementTotalsQueryRepositoryInterface;

final class RequirementTotalsQueryService
{
    public function __construct(private readonly RequirementTotalsQueryRepositoryInterface $totals)
    {
    }

    public function byEvents(array $eventIds): array
    {
        return $this->totals->totalsByEventIds($eventIds);
    }

    public function byDay(?string $propertyId, string $date): array
    {
        return $this->totals->totalsByDay($propertyId, $date);
    }

    public function byPropertyRange(string $propertyId, string $fromDate, string $toDate): array
    {
        return $this->totals->totalsByPropertyRange($propertyId, $fromDate, $toDate);
    }
}
