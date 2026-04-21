<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Core\Exception\ValidationException;

final class LaundryHandoverValidator
{
    public function validateCreate(array $data): void
    {
        $errors = [];

        if (empty($data['from_location_id'])) {
            $errors['from_location_id'] = 'from_location_id is required';
        }
        if (empty($data['to_location_id'])) {
            $errors['to_location_id'] = 'to_location_id is required';
        }
        if (($data['from_location_id'] ?? null) === ($data['to_location_id'] ?? null)) {
            $errors['to_location_id'] = 'to_location_id must be different from from_location_id';
        }

        $items = $data['items'] ?? null;
        if (!is_array($items) || $items === []) {
            $errors['items'] = 'items must be a non-empty array';
        } else {
            $seen = [];
            foreach ($items as $index => $item) {
                if (!is_array($item)) {
                    $errors[sprintf('items.%d', $index)] = 'item payload is invalid';
                    continue;
                }

                $itemId = $item['item_id'] ?? '';
                $quantitySent = $item['quantity_sent'] ?? null;

                if (!is_string($itemId) || trim($itemId) === '') {
                    $errors[sprintf('items.%d.item_id', $index)] = 'item_id is required';
                }
                if (!is_numeric($quantitySent) || (float) $quantitySent <= 0) {
                    $errors[sprintf('items.%d.quantity_sent', $index)] = 'quantity_sent must be > 0';
                }
                if (is_string($itemId) && isset($seen[$itemId])) {
                    $errors[sprintf('items.%d.item_id', $index)] = 'duplicate item_id is not allowed';
                }

                if (is_string($itemId) && trim($itemId) !== '') {
                    $seen[$itemId] = true;
                }
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateReturn(array $data): void
    {
        $errors = [];

        if (empty($data['handover_id'])) {
            $errors['handover_id'] = 'handover_id is required';
        }

        $items = $data['items'] ?? null;
        if (!is_array($items) || $items === []) {
            $errors['items'] = 'items must be a non-empty array';
        } else {
            $seen = [];
            foreach ($items as $index => $item) {
                if (!is_array($item)) {
                    $errors[sprintf('items.%d', $index)] = 'item payload is invalid';
                    continue;
                }

                $itemId = $item['item_id'] ?? '';
                $quantityReturned = $item['quantity_returned'] ?? null;

                if (!is_string($itemId) || trim($itemId) === '') {
                    $errors[sprintf('items.%d.item_id', $index)] = 'item_id is required';
                }
                if (!is_numeric($quantityReturned) || (float) $quantityReturned <= 0) {
                    $errors[sprintf('items.%d.quantity_returned', $index)] = 'quantity_returned must be > 0';
                }
                if (is_string($itemId) && isset($seen[$itemId])) {
                    $errors[sprintf('items.%d.item_id', $index)] = 'duplicate item_id is not allowed';
                }

                if (is_string($itemId) && trim($itemId) !== '') {
                    $seen[$itemId] = true;
                }
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
