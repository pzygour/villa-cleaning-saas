<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface BathroomTypeRepositoryInterface
{
    public function allActive(): array;
}
