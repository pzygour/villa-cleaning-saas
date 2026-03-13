<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class InventoryMovementDTO
{
    public function __construct(
        public string $itemId,
        public string $locationId,
        public string $transactionType,
        public int $quantity,
        public ?string $referenceType = null,
        public ?string $referenceId = null,
        public string $note = ''
    ) {
    }
}
