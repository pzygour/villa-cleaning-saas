<?php

declare(strict_types=1);

namespace App\Application\Validators;

use App\Application\DTO\CreateBookingDTO;
use App\Core\Exception\ValidationException;

final class BookingValidator
{
    public function validate(CreateBookingDTO $dto): void
    {
        $errors = [];

        if ($dto->guestCount < 1) {
            $errors['guestCount'] = 'Guest count must be greater than 0';
        }

        if ($dto->arrivalDate >= $dto->departureDate) {
            $errors['dateRange'] = 'Departure must be after arrival';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
