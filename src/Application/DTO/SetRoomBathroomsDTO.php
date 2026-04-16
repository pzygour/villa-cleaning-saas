<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class SetRoomBathroomsDTO
{
    /** @param list<array{bathroomTypeId:string,quantity:int}> $bathrooms */
    public function __construct(public string $roomId, public array $bathrooms)
    {
    }
}
