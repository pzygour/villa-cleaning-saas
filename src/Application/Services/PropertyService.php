<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\PropertyRepositoryInterface;
use App\Application\DTO\CreatePropertyDTO;
use App\Application\DTO\UpdatePropertyDTO;
use App\Application\Validators\PropertyValidator;

final class PropertyService
{
    public function __construct(
        private readonly PropertyRepositoryInterface $properties,
        private readonly PropertyValidator $validator
    ) {
    }

    public function create(CreatePropertyDTO $dto): string
    {
        $data = [
            'code' => $dto->code,
            'name' => $dto->name,
            'location_label' => $dto->locationLabel,
            'property_type' => $dto->propertyType,
            'operational_notes' => $dto->operationalNotes,
            'is_active' => $dto->isActive,
        ];
        $this->validator->validate($data);

        return $this->properties->create($data);
    }

    public function update(UpdatePropertyDTO $dto): void
    {
        $data = [
            'code' => $dto->code,
            'name' => $dto->name,
            'location_label' => $dto->locationLabel,
            'property_type' => $dto->propertyType,
            'operational_notes' => $dto->operationalNotes,
            'is_active' => $dto->isActive,
        ];
        $this->validator->validate($data);
        $this->properties->update($dto->id, $data);
    }

    public function delete(string $id): void
    {
        $this->properties->delete($id);
    }

    public function list(): array
    {
        return $this->properties->all();
    }
}
