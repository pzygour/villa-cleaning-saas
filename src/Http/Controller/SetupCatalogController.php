<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Contracts\BathroomTypeRepositoryInterface;
use App\Application\Contracts\BedTypeRepositoryInterface;

final class SetupCatalogController
{
    public function __construct(
        private BedTypeRepositoryInterface $bedTypes,
        private BathroomTypeRepositoryInterface $bathroomTypes
    ) {
    }

    public function bedTypes(): array
    {
        return ['data' => $this->bedTypes->allActive()];
    }

    public function bathroomTypes(): array
    {
        return ['data' => $this->bathroomTypes->allActive()];
    }
}
