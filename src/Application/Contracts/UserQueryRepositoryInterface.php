<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface UserQueryRepositoryInterface
{
    public function activeCleaners(): array;
}
