<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Core\Exception\ValidationException;

final class CleaningSettingsValidator
{
    public function validate(array $data): void
    {
        $errors = [];
        if (($data['mid_clean_every_days'] ?? 0) < 1) {
            $errors['mid_clean_every_days'] = 'Must be >= 1';
        }
        if (($data['min_nights_for_mid_clean'] ?? 0) < 1) {
            $errors['min_nights_for_mid_clean'] = 'Must be >= 1';
        }
        if (($data['skip_mid_clean_last_n_days'] ?? -1) < 0) {
            $errors['skip_mid_clean_last_n_days'] = 'Must be >= 0';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
