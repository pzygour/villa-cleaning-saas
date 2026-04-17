<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\CreateBookingDTO;
use App\Application\DTO\UpdateBookingDTO;
use App\Application\Services\BookingService;

final class BookingController
{
    public function __construct(private BookingService $service)
    {
    }

    public function create(array $input): array
    {
        $id = $this->service->create(new CreateBookingDTO(
            $input['property_id'],
            $input['booking_reference'] ?? null,
            $input['arrival_date'],
            $input['departure_date'],
            (int) $input['guest_count'],
            $input['notes'] ?? ''
        ));

        return ['id' => $id];
    }

    public function update(string $id, array $input): array
    {
        $this->service->update(new UpdateBookingDTO(
            $id,
            $input['booking_reference'] ?? null,
            $input['arrival_date'],
            $input['departure_date'],
            (int) $input['guest_count'],
            $input['notes'] ?? ''
        ));

        return ['updated' => true];
    }

    public function cancel(string $id): array
    {
        $this->service->cancel($id);

        return ['cancelled' => true];
    }

    public function list(string $propertyId, string $fromDate, string $toDate): array
    {
        return ['data' => $this->service->listByPropertyAndPeriod($propertyId, $fromDate, $toDate)];
    }
}
