<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\BedTypeItemRuleRepositoryInterface;
use App\Application\DTO\CreateBedTypeItemRuleDTO;
use App\Application\DTO\UpdateBedTypeItemRuleDTO;
use App\Application\Validators\ItemRuleValidator;

final class BedTypeItemRuleService
{
    public function __construct(
        private BedTypeItemRuleRepositoryInterface $rules,
        private ItemRuleValidator $validator
    ) {
    }

    public function create(CreateBedTypeItemRuleDTO $dto): string
    {
        $this->validator->validateTriggerAndQuantity($dto->triggerType, $dto->quantityPerBed, $dto->bedTypeId);

        return $this->rules->create([
            'bed_type_id' => $dto->bedTypeId,
            'item_id' => $dto->itemId,
            'trigger_type' => $dto->triggerType,
            'quantity_per_bed' => $dto->quantityPerBed,
        ]);
    }

    public function update(UpdateBedTypeItemRuleDTO $dto): void
    {
        $this->validator->validateTriggerAndQuantity($dto->triggerType, $dto->quantityPerBed, $dto->bedTypeId);

        $this->rules->update($dto->id, [
            'bed_type_id' => $dto->bedTypeId,
            'item_id' => $dto->itemId,
            'trigger_type' => $dto->triggerType,
            'quantity_per_bed' => $dto->quantityPerBed,
        ]);
    }

    public function delete(string $id): void
    {
        $this->rules->delete($id);
    }

    public function list(?string $triggerType = null): array
    {
        return $this->rules->all($triggerType);
    }
}
