<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class CleaningEventRequirementDTO
{
    /**
     * @param array<string,int> $linenItems
     * @param array<string,int> $towelItems
     */
    public function __construct(
        public string $cleaningEventId,
        public array $linenItems,
        public array $towelItems
    ) {
    }
}
