<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class PostInventoryTransactionDTO
{
    public function __construct(
        public string $itemId,
        public string $locationId,
        public string $transactionType,
        public float $quantity,
        public ?string $referenceType = null,
        public ?string $referenceId = null,
        public ?string $createdByUserId = null,
        public string $note = ''
    ) {
    }
}
