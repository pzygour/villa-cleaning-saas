<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class UpdateGuestItemRuleDTO
{
    public function __construct(
        public string $id,
        public ?string $propertyId,
        public string $itemId,
        public string $triggerType,
        public float $quantityPerGuest
    ) {
    }
}
