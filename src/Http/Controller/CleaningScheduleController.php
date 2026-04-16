<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Contracts\CleaningEventRepositoryInterface;
use App\Application\DTO\GenerateCleaningEventsDTO;
use App\Application\Services\CleaningScheduleService;

final class CleaningScheduleController
{
    public function __construct(
        private readonly CleaningScheduleService $service,
        private readonly CleaningEventRepositoryInterface $events
    ) {
    }

    public function regenerate(string $propertyId, array $input): array
    {
        $events = $this->service->regenerate(new GenerateCleaningEventsDTO(
            $propertyId,
            $input['from_date'],
            $input['to_date']
        ));

        return ['generated' => count($events), 'data' => $events];
    }

    public function list(string $propertyId, string $fromDate, string $toDate): array
    {
        return ['data' => $this->events->listByPropertyAndPeriod($propertyId, $fromDate, $toDate)];
    }
}
