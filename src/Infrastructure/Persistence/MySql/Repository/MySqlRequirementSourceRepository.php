<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\RequirementSourceRepositoryInterface;
use PDO;

final class MySqlRequirementSourceRepository implements RequirementSourceRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findEvent(string $eventId): ?array
    {
        $stmt = $this->connection->prepare('SELECT id, property_id, booking_id, event_date, event_type FROM cleaning_events WHERE id=:id');
        $stmt->execute(['id' => $eventId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function listEventsByPropertyAndPeriod(string $propertyId, string $fromDate, string $toDate): array
    {
        $stmt = $this->connection->prepare('SELECT id, property_id, booking_id, event_date, event_type FROM cleaning_events WHERE property_id=:property_id AND event_date BETWEEN :from_date AND :to_date ORDER BY event_date');
        $stmt->execute(['property_id' => $propertyId, 'from_date' => $fromDate, 'to_date' => $toDate]);

        return $stmt->fetchAll();
    }

    public function roomBedTotalsByProperty(string $propertyId): array
    {
        $stmt = $this->connection->prepare('SELECT rb.bed_type_id, SUM(rb.quantity) AS qty FROM room_beds rb INNER JOIN rooms r ON r.id = rb.room_id WHERE r.property_id=:property_id GROUP BY rb.bed_type_id');
        $stmt->execute(['property_id' => $propertyId]);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['bed_type_id']] = (float) $row['qty'];
        }

        return $map;
    }

    public function roomBathroomTotalsByProperty(string $propertyId): array
    {
        $stmt = $this->connection->prepare('SELECT rb.bathroom_type_id, SUM(rb.quantity) AS qty FROM room_bathrooms rb INNER JOIN rooms r ON r.id = rb.room_id WHERE r.property_id=:property_id GROUP BY rb.bathroom_type_id');
        $stmt->execute(['property_id' => $propertyId]);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['bathroom_type_id']] = (float) $row['qty'];
        }

        return $map;
    }

    public function bookingGuestCount(string $bookingId): ?int
    {
        $stmt = $this->connection->prepare('SELECT guest_count FROM bookings WHERE id=:id');
        $stmt->execute(['id' => $bookingId]);
        $row = $stmt->fetch();

        return $row === false ? null : (int) $row['guest_count'];
    }
}
