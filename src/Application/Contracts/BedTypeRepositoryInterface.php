<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface BedTypeRepositoryInterface
{
    public function allActive(): array;
}
