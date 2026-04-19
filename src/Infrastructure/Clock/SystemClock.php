<?php

declare(strict_types=1);

namespace App\Infrastructure\Clock;

use DateTimeImmutable;

final class SystemClock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now');
    }
}
