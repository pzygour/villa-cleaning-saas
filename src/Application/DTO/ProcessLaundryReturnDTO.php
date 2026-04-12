<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class ProcessLaundryReturnDTO
{
    /** @param array<int,array{item_id:string,quantity_returned:float}> $items */
    public function __construct(
        public string $handoverId,
        public array $items,
        public ?string $createdByUserId = null,
        public ?string $note = null
    ) {
    }
}
