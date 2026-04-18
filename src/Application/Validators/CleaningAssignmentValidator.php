<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Core\Exception\ValidationException;

final class CleaningAssignmentValidator
{
    private const STATUSES = ['assigned', 'accepted', 'completed', 'cancelled'];

    public function validateUserIds(array $userIds): void
    {
        if ($userIds === []) {
            throw new ValidationException(['user_ids' => 'At least one cleaner is required']);
        }

        foreach ($userIds as $id) {
            if (!is_string($id) || $id === '') {
                throw new ValidationException(['user_ids' => 'Each cleaner id must be a non-empty string']);
            }
        }
    }

    public function validateStatus(string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new ValidationException(['assignment_status' => 'Invalid assignment status']);
        }
    }
}
