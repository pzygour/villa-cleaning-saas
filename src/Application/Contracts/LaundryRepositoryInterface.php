<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Domain\Laundry\LaundryHandover;

interface LaundryRepositoryInterface
{
    public function saveHandover(LaundryHandover $handover): void;

    public function updateStatus(string $handoverId, string $status): void;
}
