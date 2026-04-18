<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\CreatePropertyDTO;
use App\Application\DTO\UpdatePropertyDTO;
use App\Application\Services\PropertyService;

final class PropertyController
{
    public function __construct(private PropertyService $service)
    {
    }

    public function index(): array
    {
        return ['data' => $this->service->list()];
    }

    public function create(array $input): array
    {
        $id = $this->service->create(new CreatePropertyDTO(
            $input['code'],
            $input['name'],
            $input['location_label'],
            $input['property_type'],
            $input['operational_notes'] ?? null,
            (bool) ($input['is_active'] ?? true)
        ));

        return ['id' => $id];
    }

    public function update(string $id, array $input): array
    {
        $this->service->update(new UpdatePropertyDTO(
            $id,
            $input['code'],
            $input['name'],
            $input['location_label'],
            $input['property_type'],
            $input['operational_notes'] ?? null,
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
