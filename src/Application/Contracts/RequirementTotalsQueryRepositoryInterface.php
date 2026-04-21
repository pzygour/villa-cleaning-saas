<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface RequirementTotalsQueryRepositoryInterface
{
    public function totalsByEventIds(array $eventIds): array;

    public function totalsByDay(?string $propertyId, string $date): array;

    public function totalsByPropertyRange(string $propertyId, string $fromDate, string $toDate): array;
}
