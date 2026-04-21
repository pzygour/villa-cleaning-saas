<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class UpdateAssignmentStatusDTO
{
    public function __construct(
        public string $cleaningEventId,
        public string $userId,
        public string $status
    ) {
    }
}
