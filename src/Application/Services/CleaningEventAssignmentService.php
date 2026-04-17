<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\CleaningEventAssignmentRepositoryInterface;
use App\Application\DTO\AssignCleanersDTO;
use App\Application\DTO\UpdateAssignmentStatusDTO;
use App\Application\Validators\CleaningAssignmentValidator;
use App\Core\Database\TransactionManager;

final class CleaningEventAssignmentService
{
    public function __construct(
        private CleaningEventAssignmentRepositoryInterface $assignments,
        private CleaningAssignmentValidator $validator,
        private TransactionManager $transactionManager
    ) {
    }

    public function assignCleaners(AssignCleanersDTO $dto): array
    {
        $this->validator->validateUserIds($dto->userIds);

        $this->transactionManager->transactional(function () use ($dto): void {
            $this->assignments->replaceAssignments($dto->cleaningEventId, array_values(array_unique($dto->userIds)));
        });

        return $this->assignments->byEvent($dto->cleaningEventId);
    }

    public function updateStatus(UpdateAssignmentStatusDTO $dto): void
    {
        $this->validator->validateStatus($dto->status);
        $this->assignments->updateStatus($dto->cleaningEventId, $dto->userId, $dto->status);
    }
}
