<?php

declare(strict_types=1);

namespace App\Domain\User;

enum UserRole: string
{
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case CLEANER = 'cleaner';
}
