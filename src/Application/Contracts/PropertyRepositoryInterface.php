<?php

declare(strict_types=1);

namespace App\Application\Contracts;

interface PropertyRepositoryInterface
{
    public function create(array $data): string;

    public function update(string $id, array $data): void;

    public function delete(string $id): void;

    public function findById(string $id): ?array;

    public function all(): array;
}
