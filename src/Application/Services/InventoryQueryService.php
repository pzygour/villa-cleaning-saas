<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\InventoryQueryRepositoryInterface;

final class InventoryQueryService
{
    public function __construct(private readonly InventoryQueryRepositoryInterface $query)
    {
    }

    public function balancesByLocation(string $locationId): array
    {
        return $this->query->balancesByLocation($locationId);
    }

    public function balancesByItem(string $itemId): array
    {
        return $this->query->balancesByItem($itemId);
    }

    public function movementsByLocation(string $locationId, string $fromDate, string $toDate): array
    {
        return $this->query->movementsByLocation($locationId, $fromDate, $toDate);
    }

    public function movementsByItem(string $itemId, string $fromDate, string $toDate): array
    {
        return $this->query->movementsByItem($itemId, $fromDate, $toDate);
    }

    public function eventAvailability(string $eventId, string $locationId): array
    {
        return $this->query->eventAvailability($eventId, $locationId);
    }
}
