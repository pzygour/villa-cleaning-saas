<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\GuestItemRuleRepositoryInterface;
use App\Application\DTO\CreateGuestItemRuleDTO;
use App\Application\DTO\UpdateGuestItemRuleDTO;
use App\Application\Validators\ItemRuleValidator;

final class GuestItemRuleService
{
    public function __construct(
        private GuestItemRuleRepositoryInterface $rules,
        private ItemRuleValidator $validator
    ) {
    }

    public function create(CreateGuestItemRuleDTO $dto): string
    {
        $this->validator->validateTriggerAndQuantity($dto->triggerType, $dto->quantityPerGuest, $dto->itemId);

        return $this->rules->create([
            'property_id' => $dto->propertyId,
            'item_id' => $dto->itemId,
            'trigger_type' => $dto->triggerType,
            'quantity_per_guest' => $dto->quantityPerGuest,
        ]);
    }

    public function update(UpdateGuestItemRuleDTO $dto): void
    {
        $this->validator->validateTriggerAndQuantity($dto->triggerType, $dto->quantityPerGuest, $dto->itemId);

        $this->rules->update($dto->id, [
            'property_id' => $dto->propertyId,
            'item_id' => $dto->itemId,
            'trigger_type' => $dto->triggerType,
            'quantity_per_guest' => $dto->quantityPerGuest,
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
