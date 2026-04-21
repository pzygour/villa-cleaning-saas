<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Core\Exception\ValidationException;

final class InventoryTransactionValidator
{
    private const TYPES = ['in', 'out', 'adjustment', 'laundry_out', 'laundry_in', 'reserve', 'unreserve'];

    public function validate(array $data): void
    {
        $errors = [];
        if (empty($data['item_id'])) {
            $errors['item_id'] = 'item_id is required';
        }
        if (empty($data['location_id'])) {
            $errors['location_id'] = 'location_id is required';
        }
        if (!in_array($data['transaction_type'] ?? '', self::TYPES, true)) {
            $errors['transaction_type'] = 'Invalid transaction_type';
        }
        if (!is_numeric($data['quantity'] ?? null) || (float) $data['quantity'] <= 0) {
            $errors['quantity'] = 'Quantity must be > 0';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
