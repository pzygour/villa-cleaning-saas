<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\RecalculateEventRequirementsDTO;
use App\Application\DTO\RecalculatePropertyRequirementsDTO;
use App\Application\Services\RequirementCalculationService;

final class RequirementController
{
    public function __construct(private RequirementCalculationService $service)
    {
    }

    public function recalculateEvent(string $eventId): array
    {
        return ['data' => $this->service->recalculateEvent(new RecalculateEventRequirementsDTO($eventId))];
    }

    public function recalculatePropertyRange(string $propertyId, array $input): array
    {
        return ['data' => $this->service->recalculatePropertyRange(new RecalculatePropertyRequirementsDTO(
            $propertyId,
            $input['from_date'],
            $input['to_date']
        ))];
    }
}
