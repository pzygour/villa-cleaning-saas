<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\CreateItemCatalogItemDTO;
use App\Application\DTO\UpdateItemCatalogItemDTO;
use App\Application\Services\ItemCatalogService;

final class ItemCatalogController
{
    public function __construct(private ItemCatalogService $service)
    {
    }

    public function index(?string $itemType): array
    {
        return ['data' => $this->service->list($itemType)];
    }

    public function create(array $input): array
    {
        $id = $this->service->create(new CreateItemCatalogItemDTO(
            $input['item_type'],
            $input['code'],
            $input['name'],
            $input['unit'] ?? 'piece',
            (bool) ($input['is_active'] ?? true)
        ));

        return ['id' => $id];
    }

    public function update(string $id, array $input): array
    {
        $this->service->update(new UpdateItemCatalogItemDTO(
            $id,
            $input['item_type'],
            $input['code'],
            $input['name'],
            $input['unit'] ?? 'piece',
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
