<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\ItemCatalogRepositoryInterface;
use App\Application\DTO\CreateItemCatalogItemDTO;
use App\Application\DTO\UpdateItemCatalogItemDTO;
use App\Application\Validators\ItemCatalogValidator;

final class ItemCatalogService
{
    public function __construct(
        private ItemCatalogRepositoryInterface $items,
        private ItemCatalogValidator $validator
    ) {
    }

    public function create(CreateItemCatalogItemDTO $dto): string
    {
        $data = [
            'item_type' => $dto->itemType,
            'code' => $dto->code,
            'name' => $dto->name,
            'unit' => $dto->unit,
            'is_active' => $dto->isActive,
        ];
        $this->validator->validate($data);

        return $this->items->create($data);
    }

    public function update(UpdateItemCatalogItemDTO $dto): void
    {
        $data = [
            'item_type' => $dto->itemType,
            'code' => $dto->code,
            'name' => $dto->name,
            'unit' => $dto->unit,
            'is_active' => $dto->isActive,
        ];
        $this->validator->validate($data);
        $this->items->update($dto->id, $data);
    }

    public function delete(string $id): void
    {
        $this->items->delete($id);
    }

    public function list(?string $itemType = null): array
    {
        return $this->items->all($itemType);
    }
}
