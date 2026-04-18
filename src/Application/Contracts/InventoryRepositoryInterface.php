<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface InventoryRepositoryInterface
{
    public function currentStock(string $itemId, string $locationId): int;

    public function applyTransaction(string $transactionId): void;
}
