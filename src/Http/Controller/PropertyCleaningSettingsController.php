<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\UpsertPropertyCleaningSettingsDTO;
use App\Application\Services\PropertyCleaningSettingsService;

final class PropertyCleaningSettingsController
{
    public function __construct(private readonly PropertyCleaningSettingsService $service)
    {
    }

    public function show(string $propertyId): array
    {
        return ['data' => $this->service->byProperty($propertyId)];
    }

    public function upsert(string $propertyId, array $input): array
    {
        $this->service->upsert(new UpsertPropertyCleaningSettingsDTO(
            $propertyId,
            (int) ($input['mid_clean_every_days'] ?? 4),
            (int) ($input['min_nights_for_mid_clean'] ?? 5),
            (int) ($input['skip_mid_clean_last_n_days'] ?? 3),
            (bool) ($input['merge_same_day_turnover'] ?? true)
        ));

        return ['updated' => true];
    }
}
