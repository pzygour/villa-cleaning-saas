<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\CreateRoomDTO;
use App\Application\DTO\SetRoomBathroomsDTO;
use App\Application\DTO\SetRoomBedsDTO;
use App\Application\DTO\UpdateRoomDTO;
use App\Application\Services\RoomService;

final class RoomController
{
    public function __construct(private readonly RoomService $service)
    {
    }

    public function listByProperty(string $propertyId): array
    {
        return ['data' => $this->service->listByProperty($propertyId)];
    }

    public function create(array $input): array
    {
        $id = $this->service->create(new CreateRoomDTO(
            $input['property_id'],
            $input['name'],
            $input['room_type'],
            (int) ($input['sort_order'] ?? 0),
            (bool) ($input['is_active'] ?? true)
        ));

        return ['id' => $id];
    }

    public function update(string $id, array $input): array
    {
        $this->service->update(new UpdateRoomDTO(
            $id,
            $input['name'],
            $input['room_type'],
            (int) ($input['sort_order'] ?? 0),
            (bool) ($input['is_active'] ?? true)
        ));

        return ['updated' => true];
    }

    public function setBeds(string $roomId, array $input): array
    {
        $this->service->assignBeds(new SetRoomBedsDTO($roomId, $input['beds'] ?? []));

        return ['updated' => true];
    }

    public function setBathrooms(string $roomId, array $input): array
    {
        $this->service->assignBathrooms(new SetRoomBathroomsDTO($roomId, $input['bathrooms'] ?? []));

        return ['updated' => true];
    }
}
