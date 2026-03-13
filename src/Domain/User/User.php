<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Common\EntityId;

final readonly class User
{
    public function __construct(
        public EntityId $id,
        public string $name,
        public string $email,
        public UserRole $role,
        public bool $isActive = true
    ) {
    }
}
