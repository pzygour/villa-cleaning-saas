<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface CleaningEventAssignmentRepositoryInterface
{
    public function replaceAssignments(string $cleaningEventId, array $userIds): void;

    public function updateStatus(string $cleaningEventId, string $userId, string $status): void;

    public function byEvent(string $cleaningEventId): array;
}
