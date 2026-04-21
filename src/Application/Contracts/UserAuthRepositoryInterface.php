<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface UserAuthRepositoryInterface
{
    public function findByEmail(string $email): ?array;

    public function findById(string $userId): ?array;
}
