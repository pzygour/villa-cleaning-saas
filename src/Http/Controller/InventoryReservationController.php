<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\ReserveEventRequirementsDTO;
use App\Application\DTO\UnreserveEventRequirementsDTO;
use App\Application\Services\InventoryReservationService;

final class InventoryReservationController
{
    public function __construct(private readonly InventoryReservationService $reservations)
    {
    }

    public function reserve(string $eventId, array $input): array
    {
        $data = $this->reservations->reserveForEvent(new ReserveEventRequirementsDTO(
            $eventId,
            $input['location_id'],
            $input['created_by_user_id'] ?? null
        ));

        return ['data' => $data];
    }

    public function unreserve(string $eventId, array $input): array
    {
        $data = $this->reservations->unreserveForEvent(new UnreserveEventRequirementsDTO(
            $eventId,
            $input['location_id'],
            $input['created_by_user_id'] ?? null
        ));

        return ['data' => $data];
    }
}
