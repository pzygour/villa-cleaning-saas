<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\LaundryQueryRepositoryInterface;

final class LaundryQueryService
{
    public function __construct(private LaundryQueryRepositoryInterface $query)
    {
    }

    public function openHandovers(): array
    {
        return $this->query->openHandovers();
    }

    public function handoversByPropertyDateRange(string $propertyId, string $fromDate, string $toDate): array
    {
        return $this->query->handoversByPropertyDateRange($propertyId, $fromDate, $toDate);
    }

    public function handoverDetail(string $handoverId): array
    {
        return $this->query->handoverDetail($handoverId);
    }

    public function pendingReturnQuantities(string $handoverId): array
    {
        return $this->query->pendingReturnQuantities($handoverId);
    }
}
