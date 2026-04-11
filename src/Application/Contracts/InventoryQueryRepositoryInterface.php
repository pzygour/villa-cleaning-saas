<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface InventoryQueryRepositoryInterface
{
    public function balancesByLocation(string $locationId): array;

    public function balancesByItem(string $itemId): array;

    public function movementsByLocation(string $locationId, string $fromDate, string $toDate): array;

    public function movementsByItem(string $itemId, string $fromDate, string $toDate): array;

    public function eventAvailability(string $eventId, string $locationId): array;
}
