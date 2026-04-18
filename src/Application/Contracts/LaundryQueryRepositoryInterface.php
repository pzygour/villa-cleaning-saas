<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface LaundryQueryRepositoryInterface
{
    public function openHandovers(): array;

    public function handoversByPropertyDateRange(string $propertyId, string $fromDate, string $toDate): array;

    public function handoverDetail(string $handoverId): array;

    public function pendingReturnQuantities(string $handoverId): array;
}
