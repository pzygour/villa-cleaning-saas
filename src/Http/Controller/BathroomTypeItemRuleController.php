<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\CreateBathroomTypeItemRuleDTO;
use App\Application\DTO\UpdateBathroomTypeItemRuleDTO;
use App\Application\Services\BathroomTypeItemRuleService;

final class BathroomTypeItemRuleController
{
    public function __construct(private readonly BathroomTypeItemRuleService $service)
    {
    }

    public function index(?string $triggerType): array
    {
        return ['data' => $this->service->list($triggerType)];
    }

    public function create(array $input): array
    {
        $id = $this->service->create(new CreateBathroomTypeItemRuleDTO(
            $input['bathroom_type_id'],
            $input['item_id'],
            $input['trigger_type'],
            (float) $input['quantity_per_bathroom']
        ));

        return ['id' => $id];
    }

    public function update(string $id, array $input): array
    {
        $this->service->update(new UpdateBathroomTypeItemRuleDTO(
            $id,
            $input['bathroom_type_id'],
            $input['item_id'],
            $input['trigger_type'],
            (float) $input['quantity_per_bathroom']
        ));

        return ['updated' => true];
    }

    public function delete(string $id): array
    {
        $this->service->delete($id);

        return ['deleted' => true];
    }
}
