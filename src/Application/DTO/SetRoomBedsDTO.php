<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class SetRoomBedsDTO
{
    /** @param list<array{bedTypeId:string,quantity:int}> $beds */
    public function __construct(public string $roomId, public array $beds)
    {
    }
}
