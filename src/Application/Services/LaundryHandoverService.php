<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\LaundryHandoverRepositoryInterface;
use App\Application\DTO\CreateLaundryHandoverDTO;
use App\Application\DTO\ProcessLaundryReturnDTO;
use App\Application\Validators\LaundryHandoverValidator;
use App\Core\Database\TransactionManager;
use App\Core\Exception\DomainException;
use App\Core\Exception\NotFoundException;

final class LaundryHandoverService
{
    public function __construct(
        private LaundryHandoverRepositoryInterface $handovers,
        private InventoryLedgerService $inventoryLedger,
        private LaundryHandoverValidator $validator,
        private TransactionManager $transactionManager
    ) {
    }

    public function createHandover(CreateLaundryHandoverDTO $dto): array
    {
        $payload = [
            'property_id' => $dto->propertyId,
            'from_location_id' => $dto->fromLocationId,
            'to_location_id' => $dto->toLocationId,
            'expected_return_date' => $dto->expectedReturnDate,
            'created_by_user_id' => $dto->createdByUserId,
            'note' => $dto->note,
            'items' => $dto->items,
        ];

        $this->validator->validateCreate($payload);

        return $this->transactionManager->transactional(function () use ($payload): array {
            $items = array_map(
                static fn (array $item): array => [
                    'item_id' => $item['item_id'],
                    'quantity_sent' => (float) $item['quantity_sent'],
                    'status' => 'pending',
                ],
                $payload['items']
            );

            $handoverId = $this->handovers->createHandover([
                'property_id' => $payload['property_id'],
                'from_location_id' => $payload['from_location_id'],
                'to_location_id' => $payload['to_location_id'],
                'expected_return_date' => $payload['expected_return_date'],
                'created_by_user_id' => $payload['created_by_user_id'],
                'note' => $payload['note'],
                'status' => 'pending',
            ], $items);

            foreach ($items as $item) {
                $this->inventoryLedger->apply([
                    'item_id' => $item['item_id'],
                    'location_id' => $payload['from_location_id'],
                    'transaction_type' => 'laundry_out',
                    'quantity' => $item['quantity_sent'],
                    'reference_type' => 'laundry_handover',
                    'reference_id' => $handoverId,
                    'created_by_user_id' => $payload['created_by_user_id'],
                    'note' => 'Laundry handover out',
                ]);
            }

            return ['handover_id' => $handoverId];
        });
    }

    public function processReturn(ProcessLaundryReturnDTO $dto): array
    {
        $payload = [
            'handover_id' => $dto->handoverId,
            'items' => $dto->items,
            'created_by_user_id' => $dto->createdByUserId,
            'note' => $dto->note,
        ];

        $this->validator->validateReturn($payload);

        return $this->transactionManager->transactional(function () use ($payload): array {
            $handover = $this->handovers->findHandoverById($payload['handover_id']);
            if ($handover === null) {
                throw new NotFoundException('Laundry handover not found');
            }
            if ((string) ($handover['status'] ?? '') === 'closed') {
                throw new DomainException('Laundry handover is already closed');
            }

            $itemsById = [];
            foreach ($this->handovers->handoverItems($payload['handover_id']) as $item) {
                $itemsById[$item['item_id']] = $item;
            }

            $returnedLines = [];
            foreach ($payload['items'] as $line) {
                $itemId = $line['item_id'];
                $quantityToReturn = (float) $line['quantity_returned'];

                if (!isset($itemsById[$itemId])) {
                    throw new DomainException(sprintf('Item %s is not part of this handover', $itemId));
                }

                $currentItem = $itemsById[$itemId];
                $alreadyReturned = (float) $currentItem['quantity_returned'];
                $quantitySent = (float) $currentItem['quantity_sent'];
                $newReturned = $alreadyReturned + $quantityToReturn;

                if ($newReturned - $quantitySent > 0.00001) {
                    throw new DomainException(sprintf('Returned quantity exceeds sent quantity for item %s', $itemId));
                }

                $itemStatus = $newReturned <= 0.00001
                    ? 'pending'
                    : (($quantitySent - $newReturned) <= 0.00001 ? 'returned' : 'partially_returned');

                $this->handovers->updateHandoverItemReturn($payload['handover_id'], $itemId, $newReturned, $itemStatus);
                $itemsById[$itemId]['quantity_returned'] = $newReturned;
                $itemsById[$itemId]['status'] = $itemStatus;

                $this->inventoryLedger->apply([
                    'item_id' => $itemId,
                    'location_id' => $handover['from_location_id'],
                    'transaction_type' => 'laundry_in',
                    'quantity' => $quantityToReturn,
                    'reference_type' => 'laundry_handover',
                    'reference_id' => $payload['handover_id'],
                    'created_by_user_id' => $payload['created_by_user_id'],
                    'note' => $payload['note'] ?? 'Laundry return in',
                ]);

                $returnedLines[] = [
                    'item_id' => $itemId,
                    'returned_now' => $quantityToReturn,
                    'returned_total' => $newReturned,
                    'status' => $itemStatus,
                ];
            }

            $hasPending = false;
            foreach ($itemsById as $item) {
                if ((float) $item['quantity_returned'] + 0.00001 < (float) $item['quantity_sent']) {
                    $hasPending = true;
                    break;
                }
            }

            $newStatus = $hasPending ? 'partially_returned' : 'closed';
            $returnedDate = $newStatus === 'closed' ? gmdate('Y-m-d H:i:s') : null;
            $this->handovers->updateHandoverStatus($payload['handover_id'], $newStatus, $returnedDate);

            return [
                'handover_id' => $payload['handover_id'],
                'status' => $newStatus,
                'lines' => $returnedLines,
            ];
        });
    }
}
