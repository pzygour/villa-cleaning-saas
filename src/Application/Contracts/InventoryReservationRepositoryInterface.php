<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface InventoryReservationRepositoryInterface
{
    public function requirementLinesForEvent(string $eventId): array;

    public function updateReservedQuantity(string $eventId, string $itemId, float $reservedQuantity): void;
}
