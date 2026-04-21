<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface CleaningEventRepositoryInterface
{
    public function deleteGeneratedByPropertyAndPeriod(string $propertyId, string $fromDate, string $toDate): void;

    public function createMany(array $events): void;

    public function listByPropertyAndPeriod(string $propertyId, string $fromDate, string $toDate): array;
}
