<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class UpdateItemCatalogItemDTO
{
    public function __construct(
        public string $id,
        public string $itemType,
        public string $code,
        public string $name,
        public string $unit = 'piece',
        public bool $isActive = true
    ) {
    }
}
