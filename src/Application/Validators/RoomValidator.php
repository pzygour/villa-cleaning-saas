<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Core\Exception\ValidationException;

final class RoomValidator
{
    public function validate(array $data): void
    {
        $errors = [];
        foreach (['property_id', 'name', 'room_type'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $errors[$field] = 'Required field';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateAssignments(array $items, string $idKey): void
    {
        $errors = [];
        foreach ($items as $index => $item) {
            if (empty($item[$idKey]) || (int) ($item['quantity'] ?? 0) < 1) {
                $errors[(string) $index] = 'Each assignment requires id and positive quantity';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
