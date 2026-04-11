<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\AssignCleanersDTO;
use App\Application\DTO\UpdateAssignmentStatusDTO;
use App\Application\Services\CleaningEventAssignmentService;
use App\Application\Services\UserQueryService;

final class CleaningAssignmentController
{
    public function __construct(
        private readonly CleaningEventAssignmentService $assignments,
        private readonly UserQueryService $users
    ) {
    }

    public function assign(string $eventId, array $input): array
    {
        return ['data' => $this->assignments->assignCleaners(new AssignCleanersDTO($eventId, $input['user_ids'] ?? []))];
    }

    public function updateStatus(string $eventId, string $userId, array $input): array
    {
        $this->assignments->updateStatus(new UpdateAssignmentStatusDTO($eventId, $userId, $input['assignment_status'] ?? ''));

        return ['updated' => true];
    }

    public function activeCleaners(): array
    {
        return ['data' => $this->users->activeCleaners()];
    }
}
