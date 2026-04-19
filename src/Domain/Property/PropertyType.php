<?php

declare(strict_types=1);

namespace App\Domain\Property;

enum PropertyType: string
{
    case VILLA = 'villa';
    case APARTMENT = 'apartment';
    case ROOM = 'room';
    case HOTEL_UNIT = 'hotel_unit';
}
