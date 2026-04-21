<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface BookingRepositoryInterface
{
    public function create(array $data): string;

    public function update(string $id, array $data): void;

    public function cancel(string $id): void;

    public function findById(string $id): ?array;

    public function listByPropertyAndPeriod(string $propertyId, string $fromDate, string $toDate): array;

    public function listActiveByPropertyAndPeriod(string $propertyId, string $fromDate, string $toDate): array;
}
