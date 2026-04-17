<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class UpdateBathroomTypeItemRuleDTO
{
    public function __construct(
        public string $id,
        public string $bathroomTypeId,
        public string $itemId,
        public string $triggerType,
        public float $quantityPerBathroom
    ) {
    }
}
