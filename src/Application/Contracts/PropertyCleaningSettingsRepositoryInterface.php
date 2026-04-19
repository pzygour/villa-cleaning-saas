<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface PropertyCleaningSettingsRepositoryInterface
{
    public function upsert(string $propertyId, array $settings): void;

    public function byProperty(string $propertyId): ?array;
}
