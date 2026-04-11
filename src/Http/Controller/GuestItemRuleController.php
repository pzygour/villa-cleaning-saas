<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\CreateGuestItemRuleDTO;
use App\Application\DTO\UpdateGuestItemRuleDTO;
use App\Application\Services\GuestItemRuleService;

final class GuestItemRuleController
{
    public function __construct(private readonly GuestItemRuleService $service)
    {
    }

    public function index(?string $triggerType): array
    {
        return ['data' => $this->service->list($triggerType)];
    }

    public function create(array $input): array
    {
        $id = $this->service->create(new CreateGuestItemRuleDTO(
            $input['property_id'] ?? null,
            $input['item_id'],
            $input['trigger_type'],
            (float) $input['quantity_per_guest']
        ));

        return ['id' => $id];
    }

    public function update(string $id, array $input): array
    {
        $this->service->update(new UpdateGuestItemRuleDTO(
            $id,
            $input['property_id'] ?? null,
            $input['item_id'],
            $input['trigger_type'],
            (float) $input['quantity_per_guest']
        ));

        return ['updated' => true];
    }

    public function delete(string $id): array
    {
        $this->service->delete($id);

        return ['deleted' => true];
    }
}
