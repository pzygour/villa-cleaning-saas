<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Contracts\BookingRepositoryInterface;
use App\Application\DTO\CreateBookingDTO;
use App\Application\DTO\UpdateBookingDTO;
use App\Application\Validators\BookingValidator;

final class BookingService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingValidator $validator
    ) {
    }

    public function create(CreateBookingDTO $dto): string
    {
        $data = [
            'property_id' => $dto->propertyId,
            'booking_reference' => $dto->reference,
            'source_system' => 'manual',
            'arrival_date' => $dto->arrivalDate,
            'departure_date' => $dto->departureDate,
            'guest_count' => $dto->guestCount,
            'notes' => $dto->notes,
            'status' => 'confirmed',
        ];
        $this->validator->validate($data);

        return $this->bookings->create($data);
    }

    public function update(UpdateBookingDTO $dto): void
    {
        $data = [
            'booking_reference' => $dto->reference,
            'arrival_date' => $dto->arrivalDate,
            'departure_date' => $dto->departureDate,
            'guest_count' => $dto->guestCount,
            'notes' => $dto->notes,
            'status' => 'confirmed',
        ];
        $this->validator->validate($data + ['property_id' => 'existing']);
        $this->bookings->update($dto->id, $data);
    }

    public function cancel(string $bookingId): void
    {
        $this->bookings->cancel($bookingId);
    }

    public function listByPropertyAndPeriod(string $propertyId, string $fromDate, string $toDate): array
    {
        return $this->bookings->listByPropertyAndPeriod($propertyId, $fromDate, $toDate);
    }
}
