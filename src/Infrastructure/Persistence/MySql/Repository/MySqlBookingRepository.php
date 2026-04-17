<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\BookingRepositoryInterface;
use App\Core\Support\Uuid;
use PDO;

final class MySqlBookingRepository implements BookingRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function create(array $data): string
    {
        $id = Uuid::v4();
        $stmt = $this->connection->prepare('INSERT INTO bookings (id, property_id, booking_reference, source_system, arrival_date, departure_date, guest_count, notes, status, created_at, updated_at)
        VALUES (:id,:property_id,:booking_reference,:source_system,:arrival_date,:departure_date,:guest_count,:notes,:status,NOW(),NOW())');
        $stmt->execute([
            'id' => $id,
            'property_id' => $data['property_id'],
            'booking_reference' => $data['booking_reference'],
            'source_system' => $data['source_system'],
            'arrival_date' => $data['arrival_date'],
            'departure_date' => $data['departure_date'],
            'guest_count' => $data['guest_count'],
            'notes' => $data['notes'],
            'status' => $data['status'],
        ]);

        return $id;
    }

    public function update(string $id, array $data): void
    {
        $stmt = $this->connection->prepare('UPDATE bookings SET booking_reference=:booking_reference, arrival_date=:arrival_date, departure_date=:departure_date, guest_count=:guest_count, notes=:notes, status=:status, updated_at=NOW() WHERE id=:id');
        $stmt->execute([
            'id' => $id,
            'booking_reference' => $data['booking_reference'],
            'arrival_date' => $data['arrival_date'],
            'departure_date' => $data['departure_date'],
            'guest_count' => $data['guest_count'],
            'notes' => $data['notes'],
            'status' => $data['status'],
        ]);
    }

    public function cancel(string $id): void
    {
        $this->connection->prepare('UPDATE bookings SET status = "cancelled", updated_at=NOW() WHERE id=:id')->execute(['id' => $id]);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM bookings WHERE id=:id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function listByPropertyAndPeriod(string $propertyId, string $fromDate, string $toDate): array
    {
        $stmt = $this->connection->prepare('SELECT * FROM bookings WHERE property_id=:property_id AND arrival_date <= :to_date AND departure_date >= :from_date ORDER BY arrival_date');
        $stmt->execute(['property_id' => $propertyId, 'from_date' => $fromDate, 'to_date' => $toDate]);

        return $stmt->fetchAll();
    }

    public function listActiveByPropertyAndPeriod(string $propertyId, string $fromDate, string $toDate): array
    {
        $stmt = $this->connection->prepare('SELECT * FROM bookings WHERE property_id=:property_id AND status="confirmed" AND arrival_date <= :to_date AND departure_date >= :from_date ORDER BY arrival_date');
        $stmt->execute(['property_id' => $propertyId, 'from_date' => $fromDate, 'to_date' => $toDate]);

        return $stmt->fetchAll();
    }
}
