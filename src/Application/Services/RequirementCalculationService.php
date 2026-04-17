<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\BathroomTypeItemRuleRepositoryInterface;
use App\Application\Contracts\BedTypeItemRuleRepositoryInterface;
use App\Application\Contracts\CleaningEventRequirementRepositoryInterface;
use App\Application\Contracts\GuestItemRuleRepositoryInterface;
use App\Application\Contracts\RequirementSourceRepositoryInterface;
use App\Application\DTO\RecalculateEventRequirementsDTO;
use App\Application\DTO\RecalculatePropertyRequirementsDTO;
use App\Core\Database\TransactionManager;
use App\Core\Exception\NotFoundException;

final class RequirementCalculationService
{
    public function __construct(
        private RequirementSourceRepositoryInterface $source,
        private BedTypeItemRuleRepositoryInterface $bedRules,
        private BathroomTypeItemRuleRepositoryInterface $bathroomRules,
        private GuestItemRuleRepositoryInterface $guestRules,
        private CleaningEventRequirementRepositoryInterface $requirements,
        private TransactionManager $transactionManager
    ) {
    }

    public function recalculateEvent(RecalculateEventRequirementsDTO $dto): array
    {
        $event = $this->source->findEvent($dto->eventId);
        if ($event === null) {
            throw new NotFoundException('Cleaning event not found');
        }

        $lines = $this->buildRequirementLines($event);
        $this->transactionManager->transactional(function () use ($event, $lines): void {
            $this->requirements->replaceForEvent($event['id'], $lines);
        });

        return $lines;
    }

    public function recalculatePropertyRange(RecalculatePropertyRequirementsDTO $dto): array
    {
        $events = $this->source->listEventsByPropertyAndPeriod($dto->propertyId, $dto->fromDate, $dto->toDate);
        $result = [];

        $this->transactionManager->transactional(function () use ($events, &$result): void {
            foreach ($events as $event) {
                $lines = $this->buildRequirementLines($event);
                $this->requirements->replaceForEvent($event['id'], $lines);
                $result[$event['id']] = $lines;
            }
        });

        return $result;
    }

    private function buildRequirementLines(array $event): array
    {
        $trigger = $event['event_type'];
        $propertyId = $event['property_id'];

        $roomBeds = $this->source->roomBedTotalsByProperty($propertyId);
        $roomBathrooms = $this->source->roomBathroomTotalsByProperty($propertyId);
        $bedRules = $this->bedRules->byTrigger($trigger);
        $bathRules = $this->bathroomRules->byTrigger($trigger);
        $guestRules = $this->guestRules->resolveForPropertyAndTrigger($propertyId, $trigger);

        $itemTotals = [];

        foreach ($bedRules as $rule) {
            $bedQty = (float) ($roomBeds[$rule['bed_type_id']] ?? 0.0);
            if ($bedQty === 0.0) {
                continue;
            }
            $itemTotals[$rule['item_id']] = ($itemTotals[$rule['item_id']] ?? 0.0) + ($bedQty * (float) $rule['quantity_per_bed']);
        }

        foreach ($bathRules as $rule) {
            $bathQty = (float) ($roomBathrooms[$rule['bathroom_type_id']] ?? 0.0);
            if ($bathQty === 0.0) {
                continue;
            }
            $itemTotals[$rule['item_id']] = ($itemTotals[$rule['item_id']] ?? 0.0) + ($bathQty * (float) $rule['quantity_per_bathroom']);
        }

        // MVP choice (Option A): for departure_arrival with booking_id NULL, skip guest-based requirements.
        if (!($trigger === 'departure_arrival' && $event['booking_id'] === null)) {
            $guestCount = $event['booking_id'] !== null ? ($this->source->bookingGuestCount($event['booking_id']) ?? 0) : 0;
            foreach ($guestRules as $rule) {
                $itemTotals[$rule['item_id']] = ($itemTotals[$rule['item_id']] ?? 0.0) + ($guestCount * (float) $rule['quantity_per_guest']);
            }
        }

        $lines = [];
        foreach ($itemTotals as $itemId => $qty) {
            if ($qty <= 0) {
                continue;
            }
            $lines[] = ['item_id' => $itemId, 'required_quantity' => round($qty, 2)];
        }

        return $lines;
    }
}
