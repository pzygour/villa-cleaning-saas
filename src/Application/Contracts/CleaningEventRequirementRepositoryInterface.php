<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface CleaningEventRequirementRepositoryInterface
{
    public function replaceForEvent(string $cleaningEventId, array $lines): void;
}
