<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class CreateBedTypeItemRuleDTO
{
    public function __construct(
        public string $bedTypeId,
        public string $itemId,
        public string $triggerType,
        public float $quantityPerBed
    ) {
    }
}
