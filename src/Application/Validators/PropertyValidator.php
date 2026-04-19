<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Core\Exception\ValidationException;

final class PropertyValidator
{
    public function validate(array $data): void
    {
        $errors = [];

        foreach (['code', 'name', 'location_label', 'property_type'] as $field) {
            if (empty($data[$field])) {
                $errors[$field] = 'Required field';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
