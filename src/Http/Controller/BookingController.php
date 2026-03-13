<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Services\BookingService;

final class BookingController
{
    public function __construct(private readonly BookingService $service)
    {
    }

    public function store(array $input): void
    {
        // HTTP-to-DTO mapping only.
    }
}
