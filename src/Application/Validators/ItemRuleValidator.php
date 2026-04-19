<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Core\Exception\ValidationException;

final class ItemRuleValidator
{
    private const TRIGGERS = ['arrival', 'departure', 'departure_arrival', 'mid_stay'];

    public function validateTriggerAndQuantity(string $triggerType, float $quantity, string $idLabel): void
    {
        $errors = [];

        if (!in_array($triggerType, self::TRIGGERS, true)) {
            $errors['trigger_type'] = 'Invalid trigger type';
        }

        if ($quantity < 0) {
            $errors['quantity'] = 'Quantity must be >= 0';
        }

        if ($idLabel === '') {
            $errors['id'] = 'Missing id field';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
