<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Core\Exception\ValidationException;

final class ItemCatalogValidator
{
    public function validate(array $data): void
    {
        $errors = [];
        if (!in_array($data['item_type'] ?? '', ['linen', 'towel'], true)) {
            $errors['item_type'] = 'item_type must be linen or towel';
        }
        foreach (['code', 'name', 'unit'] as $field) {
            if (empty($data[$field])) {
                $errors[$field] = 'Required field';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
