<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface BathroomTypeItemRuleRepositoryInterface
{
    public function create(array $data): string;

    public function update(string $id, array $data): void;

    public function delete(string $id): void;

    public function all(?string $triggerType = null): array;

    public function byTrigger(string $triggerType): array;
}
