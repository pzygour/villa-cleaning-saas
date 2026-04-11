<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\InventoryLedgerRepositoryInterface;
use App\Application\DTO\PostInventoryTransactionDTO;
use App\Application\Validators\InventoryTransactionValidator;
use App\Core\Database\TransactionManager;
use App\Core\Exception\DomainException;

final class InventoryLedgerService
{
    public function __construct(
        private readonly InventoryLedgerRepositoryInterface $ledger,
        private readonly InventoryTransactionValidator $validator,
        private readonly TransactionManager $transactionManager
    ) {
    }

    public function post(PostInventoryTransactionDTO $dto): string
    {
        $data = [
            'item_id' => $dto->itemId,
            'location_id' => $dto->locationId,
            'transaction_type' => $dto->transactionType,
            'quantity' => $dto->quantity,
            'reference_type' => $dto->referenceType,
            'reference_id' => $dto->referenceId,
            'created_by_user_id' => $dto->createdByUserId,
            'note' => $dto->note,
        ];
        $this->validator->validate($data);

        return $this->transactionManager->transactional(fn() => $this->apply($data));
    }

    /** @param array<string,mixed> $data */
    public function apply(array $data): string
    {
        $balance = $this->ledger->findBalance($data['location_id'], $data['item_id']) ?? ['on_hand_quantity' => 0.0, 'reserved_quantity' => 0.0];
        $onHand = (float) $balance['on_hand_quantity'];
        $reserved = (float) $balance['reserved_quantity'];
        $qty = (float) $data['quantity'];

        switch ($data['transaction_type']) {
            case 'in':
                $onHand += $qty;
                break;
            case 'out':
                if ($onHand - $qty < 0) {
                    throw new DomainException('Insufficient on_hand quantity');
                }
                $onHand -= $qty;
                break;
            case 'adjustment':
                $onHand += $qty;
                break;
            case 'reserve':
                if (($onHand - $reserved) < $qty) {
                    throw new DomainException('Insufficient available stock to reserve');
                }
                $reserved += $qty;
                break;
            case 'unreserve':
                if ($reserved - $qty < 0) {
                    throw new DomainException('Cannot unreserve below zero');
                }
                $reserved -= $qty;
                break;
            default:
                throw new DomainException('Unsupported transaction type');
        }

        $this->ledger->upsertBalance($data['location_id'], $data['item_id'], $onHand, $reserved);

        return $this->ledger->createTransaction($data);
    }
}
