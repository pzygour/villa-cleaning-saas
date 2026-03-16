<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\PropertyCleaningSettingsRepositoryInterface;
use App\Application\DTO\UpsertPropertyCleaningSettingsDTO;
use App\Application\Validators\CleaningSettingsValidator;

final class PropertyCleaningSettingsService
{
    public function __construct(
        private readonly PropertyCleaningSettingsRepositoryInterface $settings,
        private readonly CleaningSettingsValidator $validator
    ) {
    }

    public function upsert(UpsertPropertyCleaningSettingsDTO $dto): void
    {
        $payload = [
            'mid_clean_every_days' => $dto->midCleanEveryDays,
            'min_nights_for_mid_clean' => $dto->minNightsForMidClean,
            'skip_mid_clean_last_n_days' => $dto->skipMidCleanLastNDays,
            'merge_same_day_turnover' => $dto->mergeSameDayTurnover,
        ];
        $this->validator->validate($payload);

        $this->settings->upsert($dto->propertyId, $payload);
    }

    public function byProperty(string $propertyId): ?array
    {
        return $this->settings->byProperty($propertyId);
    }
}
