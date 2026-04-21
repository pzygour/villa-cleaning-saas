<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\CreateInventoryLocationDTO;
use App\Application\DTO\UpdateInventoryLocationDTO;
use App\Application\Services\InventoryLocationService;

final class InventoryLocationController
{
    public function __construct(private InventoryLocationService $service)
    {
    }

    public function index(?string $locationType): array
    {
        return ['data' => $this->service->list($locationType)];
    }

    public function create(array $input): array
    {
        $id = $this->service->create(new CreateInventoryLocationDTO(
            $input['code'],
            $input['name'],
            $input['location_type'],
            $input['property_id'] ?? null,
            (bool) ($input['is_active'] ?? true)
        ));

        return ['id' => $id];
    }

    public function update(string $id, array $input): array
    {
        $this->service->update(new UpdateInventoryLocationDTO(
            $id,
            $input['code'],
            $input['name'],
            $input['location_type'],
            $input['property_id'] ?? null,
            (bool) ($input['is_active'] ?? true)
        ));

        return ['updated' => true];
    }

    public function delete(string $id): array
    {
        $this->service->delete($id);

        return ['deleted' => true];
    }
}
