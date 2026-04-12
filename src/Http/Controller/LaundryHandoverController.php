<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\CreateLaundryHandoverDTO;
use App\Application\DTO\ProcessLaundryReturnDTO;
use App\Application\Services\LaundryHandoverService;

final class LaundryHandoverController
{
    public function __construct(private readonly LaundryHandoverService $handovers)
    {
    }

    public function create(array $input): array
    {
        return $this->handovers->createHandover(new CreateLaundryHandoverDTO(
            $input['from_location_id'],
            $input['to_location_id'],
            $input['items'] ?? [],
            $input['property_id'] ?? null,
            $input['expected_return_date'] ?? null,
            $input['created_by_user_id'] ?? null,
            $input['note'] ?? null
        ));
    }

    public function processReturn(string $handoverId, array $input): array
    {
        return $this->handovers->processReturn(new ProcessLaundryReturnDTO(
            $handoverId,
            $input['items'] ?? [],
            $input['created_by_user_id'] ?? null,
            $input['note'] ?? null
        ));
    }
}
