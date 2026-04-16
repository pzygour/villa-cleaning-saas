<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class UpdateBedTypeItemRuleDTO
{
    public function __construct(
        public string $id,
        public string $bedTypeId,
        public string $itemId,
        public string $triggerType,
        public float $quantityPerBed
    ) {
    }
}
