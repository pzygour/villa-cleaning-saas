<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class CreateGuestItemRuleDTO
{
    public function __construct(
        public ?string $propertyId,
        public string $itemId,
        public string $triggerType,
        public float $quantityPerGuest
    ) {
    }
}
