<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\RoomRepositoryInterface;
use App\Application\DTO\CreateRoomDTO;
use App\Application\DTO\SetRoomBathroomsDTO;
use App\Application\DTO\SetRoomBedsDTO;
use App\Application\DTO\UpdateRoomDTO;
use App\Application\Validators\RoomValidator;

final class RoomService
{
    public function __construct(
        private RoomRepositoryInterface $rooms,
        private RoomValidator $validator
    ) {
    }

    public function create(CreateRoomDTO $dto): string
    {
        $data = [
            'property_id' => $dto->propertyId,
            'name' => $dto->name,
            'room_type' => $dto->roomType,
            'sort_order' => $dto->sortOrder,
            'is_active' => $dto->isActive,
        ];
        $this->validator->validate($data);

        return $this->rooms->create($data);
    }

    public function update(UpdateRoomDTO $dto): void
    {
        $data = [
            'name' => $dto->name,
            'room_type' => $dto->roomType,
            'sort_order' => $dto->sortOrder,
            'is_active' => $dto->isActive,
        ];
        $this->rooms->update($dto->id, $data);
    }

    public function assignBeds(SetRoomBedsDTO $dto): void
    {
        $this->validator->validateAssignments($dto->beds, 'bedTypeId');
        $beds = array_map(static fn(array $item): array => [
            'bed_type_id' => $item['bedTypeId'],
            'quantity' => (int) $item['quantity'],
        ], $dto->beds);

        $this->rooms->replaceBeds($dto->roomId, $beds);
    }

    public function assignBathrooms(SetRoomBathroomsDTO $dto): void
    {
        $this->validator->validateAssignments($dto->bathrooms, 'bathroomTypeId');
        $bathrooms = array_map(static fn(array $item): array => [
            'bathroom_type_id' => $item['bathroomTypeId'],
            'quantity' => (int) $item['quantity'],
        ], $dto->bathrooms);

        $this->rooms->replaceBathrooms($dto->roomId, $bathrooms);
    }

    public function listByProperty(string $propertyId): array
    {
        return $this->rooms->listByProperty($propertyId);
    }
}
