<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class CreateBathroomTypeItemRuleDTO
{
    public function __construct(
        public string $bathroomTypeId,
        public string $itemId,
        public string $triggerType,
        public float $quantityPerBathroom
    ) {
    }
}
