<?php

declare(strict_types=1);

namespace App\Domain\Cleaning;

enum CleaningEventType: string
{
    case ARRIVAL = 'arrival';
    case DEPARTURE = 'departure';
    case DEPARTURE_ARRIVAL = 'departure_arrival';
    case MID_STAY = 'mid_stay';
}
