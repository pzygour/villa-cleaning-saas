<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class CreateItemCatalogItemDTO
{
    public function __construct(
        public string $itemType,
        public string $code,
        public string $name,
        public string $unit = 'piece',
        public bool $isActive = true
    ) {
    }
}
