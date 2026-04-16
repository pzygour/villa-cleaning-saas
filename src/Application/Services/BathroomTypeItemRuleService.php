<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\BathroomTypeItemRuleRepositoryInterface;
use App\Application\DTO\CreateBathroomTypeItemRuleDTO;
use App\Application\DTO\UpdateBathroomTypeItemRuleDTO;
use App\Application\Validators\ItemRuleValidator;

final class BathroomTypeItemRuleService
{
    public function __construct(
        private readonly BathroomTypeItemRuleRepositoryInterface $rules,
        private readonly ItemRuleValidator $validator
    ) {
    }

    public function create(CreateBathroomTypeItemRuleDTO $dto): string
    {
        $this->validator->validateTriggerAndQuantity($dto->triggerType, $dto->quantityPerBathroom, $dto->bathroomTypeId);

        return $this->rules->create([
            'bathroom_type_id' => $dto->bathroomTypeId,
            'item_id' => $dto->itemId,
            'trigger_type' => $dto->triggerType,
            'quantity_per_bathroom' => $dto->quantityPerBathroom,
        ]);
    }

    public function update(UpdateBathroomTypeItemRuleDTO $dto): void
    {
        $this->validator->validateTriggerAndQuantity($dto->triggerType, $dto->quantityPerBathroom, $dto->bathroomTypeId);

        $this->rules->update($dto->id, [
            'bathroom_type_id' => $dto->bathroomTypeId,
            'item_id' => $dto->itemId,
            'trigger_type' => $dto->triggerType,
            'quantity_per_bathroom' => $dto->quantityPerBathroom,
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
