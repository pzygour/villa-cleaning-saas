<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface InventoryLedgerRepositoryInterface
{
    public function createTransaction(array $data): string;

    public function findBalance(string $locationId, string $itemId): ?array;

    public function upsertBalance(string $locationId, string $itemId, float $onHand, float $reserved): void;
}
