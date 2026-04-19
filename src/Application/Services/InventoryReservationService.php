<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\InventoryReservationRepositoryInterface;
use App\Application\DTO\ReserveEventRequirementsDTO;
use App\Application\DTO\UnreserveEventRequirementsDTO;
use App\Core\Database\TransactionManager;

final class InventoryReservationService
{
    public function __construct(
        private InventoryReservationRepositoryInterface $requirements,
        private InventoryLedgerService $ledger,
        private TransactionManager $transactionManager
    ) {
    }

    public function reserveForEvent(ReserveEventRequirementsDTO $dto): array
    {
        $lines = $this->requirements->requirementLinesForEvent($dto->eventId);
        $reservedLines = [];

        $this->transactionManager->transactional(function () use ($dto, $lines, &$reservedLines): void {
            foreach ($lines as $line) {
                $missing = (float) $line['required_quantity'] - (float) $line['reserved_quantity'];
                if ($missing <= 0) {
                    continue;
                }

                try {
                    $this->ledger->apply([
                        'item_id' => $line['item_id'],
                        'location_id' => $dto->locationId,
                        'transaction_type' => 'reserve',
                        'quantity' => $missing,
                        'reference_type' => 'cleaning_event',
                        'reference_id' => $dto->eventId,
                        'created_by_user_id' => $dto->createdByUserId,
                        'note' => 'Reserve for event requirements',
                    ]);

                    $newReserved = (float) $line['reserved_quantity'] + $missing;
                    $this->requirements->updateReservedQuantity($dto->eventId, $line['item_id'], $newReserved);
                    $reservedLines[] = ['item_id' => $line['item_id'], 'reserved' => $missing];
                } catch (\Throwable) {
                    // keep partial reservation transparent for MVP visibility
                    continue;
                }
            }
        });

        return $reservedLines;
    }

    public function unreserveForEvent(UnreserveEventRequirementsDTO $dto): array
    {
        $lines = $this->requirements->requirementLinesForEvent($dto->eventId);
        $released = [];

        $this->transactionManager->transactional(function () use ($dto, $lines, &$released): void {
            foreach ($lines as $line) {
                $currentReserved = (float) $line['reserved_quantity'];
                if ($currentReserved <= 0) {
                    continue;
                }

                $this->ledger->apply([
                    'item_id' => $line['item_id'],
                    'location_id' => $dto->locationId,
                    'transaction_type' => 'unreserve',
                    'quantity' => $currentReserved,
                    'reference_type' => 'cleaning_event',
                    'reference_id' => $dto->eventId,
                    'created_by_user_id' => $dto->createdByUserId,
                    'note' => 'Unreserve for event requirements',
                ]);

                $this->requirements->updateReservedQuantity($dto->eventId, $line['item_id'], 0.0);
                $released[] = ['item_id' => $line['item_id'], 'unreserved' => $currentReserved];
            }
        });

        return $released;
    }
}
