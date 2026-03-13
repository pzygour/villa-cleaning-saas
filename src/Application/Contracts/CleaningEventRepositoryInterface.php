<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Domain\Cleaning\CleaningEvent;
use DateTimeImmutable;

interface CleaningEventRepositoryInterface
{
    public function saveBatch(array $events): void;

    /** @return list<CleaningEvent> */
    public function between(DateTimeImmutable $from, DateTimeImmutable $to): array;
}
