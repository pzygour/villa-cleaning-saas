<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\LaundryRepositoryInterface;

final class LaundryService
{
    public function __construct(private readonly LaundryRepositoryInterface $laundry)
    {
    }

    public function createHandover(array $payload): void
    {
        // Handover + inventory coupling workflow will be implemented next phase.
    }
}
