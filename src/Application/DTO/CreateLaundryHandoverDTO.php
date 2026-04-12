<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class CreateLaundryHandoverDTO
{
    /** @param array<int,array{item_id:string,quantity_sent:float}> $items */
    public function __construct(
        public string $fromLocationId,
        public string $toLocationId,
        public array $items,
        public ?string $propertyId = null,
        public ?string $expectedReturnDate = null,
        public ?string $createdByUserId = null,
        public ?string $note = null
    ) {
    }
}
