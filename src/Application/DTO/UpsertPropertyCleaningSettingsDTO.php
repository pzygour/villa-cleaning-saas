<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class UpsertPropertyCleaningSettingsDTO
{
    public function __construct(
        public string $propertyId,
        public int $midCleanEveryDays = 4,
        public int $minNightsForMidClean = 5,
        public int $skipMidCleanLastNDays = 3,
        public bool $mergeSameDayTurnover = true
    ) {
    }
}
