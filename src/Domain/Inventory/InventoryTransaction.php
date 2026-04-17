<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

use App\Domain\Common\EntityId;
use DateTimeImmutable;

final class InventoryTransaction
{
    public function __construct(
        public EntityId $id,
        public EntityId $itemId,
        public EntityId $locationId,
        public InventoryTransactionType $type,
        public int $quantity,
        public DateTimeImmutable $createdAt,
        public ?EntityId $referenceId = null
    ) {
    }
}
