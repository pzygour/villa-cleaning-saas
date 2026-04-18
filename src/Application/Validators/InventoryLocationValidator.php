<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Core\Exception\ValidationException;

final class InventoryLocationValidator
{
    private const TYPES = ['warehouse', 'property', 'laundry_vendor', 'vehicle'];

    public function validate(array $data): void
    {
        $errors = [];
        foreach (['code', 'name', 'location_type'] as $field) {
            if (empty($data[$field])) {
                $errors[$field] = 'Required field';
            }
        }

        if (!in_array($data['location_type'] ?? '', self::TYPES, true)) {
            $errors['location_type'] = 'Invalid location_type';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
