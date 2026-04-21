<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\InventoryRepositoryInterface;
use App\Application\DTO\InventoryMovementDTO;

final class InventoryService
{
    public function __construct(private InventoryRepositoryInterface $inventory)
    {
    }

    public function registerMovement(InventoryMovementDTO $dto): void
    {
        // Stock reservation and movement posting orchestration will be implemented next phase.
    }
}
