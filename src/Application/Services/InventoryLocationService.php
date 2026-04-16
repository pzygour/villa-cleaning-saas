<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\InventoryLocationRepositoryInterface;
use App\Application\DTO\CreateInventoryLocationDTO;
use App\Application\DTO\UpdateInventoryLocationDTO;
use App\Application\Validators\InventoryLocationValidator;

final class InventoryLocationService
{
    public function __construct(
        private readonly InventoryLocationRepositoryInterface $locations,
        private readonly InventoryLocationValidator $validator
    ) {
    }

    public function create(CreateInventoryLocationDTO $dto): string
    {
        $data = [
            'code' => $dto->code,
            'name' => $dto->name,
            'location_type' => $dto->locationType,
            'property_id' => $dto->propertyId,
            'is_active' => $dto->isActive,
        ];
        $this->validator->validate($data);

        return $this->locations->create($data);
    }

    public function update(UpdateInventoryLocationDTO $dto): void
    {
        $data = [
            'code' => $dto->code,
            'name' => $dto->name,
            'location_type' => $dto->locationType,
            'property_id' => $dto->propertyId,
            'is_active' => $dto->isActive,
        ];
        $this->validator->validate($data);

        $this->locations->update($dto->id, $data);
    }

    public function delete(string $id): void
    {
        $this->locations->delete($id);
    }

    public function list(?string $locationType = null): array
    {
        return $this->locations->all($locationType);
    }
}
