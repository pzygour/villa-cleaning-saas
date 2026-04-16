<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Core\Exception\ValidationException;
use DateTimeImmutable;

final class BookingValidator
{
    public function validate(array $data): void
    {
        $errors = [];

        if (empty($data['property_id'])) {
            $errors['property_id'] = 'Property is required';
        }

        if ((int) ($data['guest_count'] ?? 0) < 1) {
            $errors['guest_count'] = 'Guest count must be greater than 0';
        }

        try {
            $arrival = new DateTimeImmutable((string) ($data['arrival_date'] ?? ''));
            $departure = new DateTimeImmutable((string) ($data['departure_date'] ?? ''));
            if ($arrival >= $departure) {
                $errors['date_range'] = 'Departure must be after arrival';
            }
        } catch (\Throwable) {
            $errors['dates'] = 'Invalid arrival/departure date format';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
