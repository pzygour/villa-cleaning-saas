<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\PostInventoryTransactionDTO;
use App\Application\Services\InventoryLedgerService;

final class InventoryTransactionController
{
    public function __construct(private readonly InventoryLedgerService $ledger)
    {
    }

    public function post(array $input): array
    {
        $id = $this->ledger->post(new PostInventoryTransactionDTO(
            $input['item_id'],
            $input['location_id'],
            $input['transaction_type'],
            (float) $input['quantity'],
            $input['reference_type'] ?? null,
            $input['reference_id'] ?? null,
            $input['created_by_user_id'] ?? null,
            $input['note'] ?? ''
        ));

        return ['id' => $id];
    }
}
