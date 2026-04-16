<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\CreateBedTypeItemRuleDTO;
use App\Application\DTO\UpdateBedTypeItemRuleDTO;
use App\Application\Services\BedTypeItemRuleService;

final class BedTypeItemRuleController
{
    public function __construct(private readonly BedTypeItemRuleService $service)
    {
    }

    public function index(?string $triggerType): array
    {
        return ['data' => $this->service->list($triggerType)];
    }

    public function create(array $input): array
    {
        $id = $this->service->create(new CreateBedTypeItemRuleDTO(
            $input['bed_type_id'],
            $input['item_id'],
            $input['trigger_type'],
            (float) $input['quantity_per_bed']
        ));

        return ['id' => $id];
    }

    public function update(string $id, array $input): array
    {
        $this->service->update(new UpdateBedTypeItemRuleDTO(
            $id,
            $input['bed_type_id'],
            $input['item_id'],
            $input['trigger_type'],
            (float) $input['quantity_per_bed']
        ));

        return ['updated' => true];
    }

    public function delete(string $id): array
    {
        $this->service->delete($id);

        return ['deleted' => true];
    }
}
