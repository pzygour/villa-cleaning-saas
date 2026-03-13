<?php

declare(strict_types=1);

namespace App\Application\Contracts;

use App\Domain\Property\Property;

interface PropertyRepositoryInterface
{
    public function save(Property $property): void;

    public function findById(string $id): ?Property;

    /** @return list<Property> */
    public function all(): array;
}
