<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\UserQueryRepositoryInterface;

final class UserQueryService
{
    public function __construct(private readonly UserQueryRepositoryInterface $users)
    {
    }

    public function activeCleaners(): array
    {
        return $this->users->activeCleaners();
    }
}
