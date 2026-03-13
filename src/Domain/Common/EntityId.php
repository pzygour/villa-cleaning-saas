<?php

declare(strict_types=1);

namespace App\Domain\Common;

readonly class EntityId
{
    public function __construct(public string $value)
    {
    }
}
