<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Services\LaundryQueryService;

final class LaundryQueryController
{
    public function __construct(private LaundryQueryService $queries)
    {
    }

    public function openHandovers(): array
    {
        return $this->queries->openHandovers();
    }

    public function handoversByPropertyDateRange(string $propertyId, string $fromDate, string $toDate): array
    {
        return $this->queries->handoversByPropertyDateRange($propertyId, $fromDate, $toDate);
    }

    public function handoverDetail(string $handoverId): array
    {
        return $this->queries->handoverDetail($handoverId);
    }

    public function pendingReturnQuantities(string $handoverId): array
    {
        return $this->queries->pendingReturnQuantities($handoverId);
    }
}
