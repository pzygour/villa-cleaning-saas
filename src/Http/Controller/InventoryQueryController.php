<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Services\InventoryQueryService;

final class InventoryQueryController
{
    public function __construct(private readonly InventoryQueryService $query)
    {
    }

    public function balancesByLocation(string $locationId): array
    {
        return ['data' => $this->query->balancesByLocation($locationId)];
    }

    public function balancesByItem(string $itemId): array
    {
        return ['data' => $this->query->balancesByItem($itemId)];
    }

    public function movementsByLocation(string $locationId, string $fromDate, string $toDate): array
    {
        return ['data' => $this->query->movementsByLocation($locationId, $fromDate, $toDate)];
    }

    public function movementsByItem(string $itemId, string $fromDate, string $toDate): array
    {
        return ['data' => $this->query->movementsByItem($itemId, $fromDate, $toDate)];
    }

    public function eventAvailability(string $eventId, string $locationId): array
    {
        return ['data' => $this->query->eventAvailability($eventId, $locationId)];
    }
}
