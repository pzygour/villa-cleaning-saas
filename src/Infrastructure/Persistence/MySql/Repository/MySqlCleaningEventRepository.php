<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\CleaningEventRepositoryInterface;
use App\Core\Support\Uuid;
use PDO;

final class MySqlCleaningEventRepository implements CleaningEventRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function deleteGeneratedByPropertyAndPeriod(string $propertyId, string $fromDate, string $toDate): void
    {
        $stmt = $this->connection->prepare('DELETE FROM cleaning_events WHERE property_id=:property_id AND generated_by="system" AND event_date BETWEEN :from_date AND :to_date');
        $stmt->execute(['property_id' => $propertyId, 'from_date' => $fromDate, 'to_date' => $toDate]);
    }

    public function createMany(array $events): void
    {
        $stmt = $this->connection->prepare('INSERT IGNORE INTO cleaning_events (id, property_id, booking_id, event_date, event_type, status, operational_notes, generated_by, created_at, updated_at)
        VALUES (:id,:property_id,:booking_id,:event_date,:event_type,:status,:operational_notes,:generated_by,NOW(),NOW())');

        $seen = [];
        foreach ($events as $event) {
            $key = sprintf(
                '%s|%s|%s|%s|%s',
                $event['property_id'],
                $event['event_date'],
                $event['event_type'],
                $event['booking_id'] ?? 'null',
                $event['generated_by']
            );
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $stmt->execute([
                'id' => Uuid::v4(),
                'property_id' => $event['property_id'],
                'booking_id' => $event['booking_id'],
                'event_date' => $event['event_date'],
                'event_type' => $event['event_type'],
                'status' => $event['status'],
                'operational_notes' => $event['operational_notes'],
                'generated_by' => $event['generated_by'],
            ]);
        }
    }

    public function listByPropertyAndPeriod(string $propertyId, string $fromDate, string $toDate): array
    {
        $stmt = $this->connection->prepare('SELECT * FROM cleaning_events WHERE property_id=:property_id AND event_date BETWEEN :from_date AND :to_date ORDER BY event_date, event_type');
        $stmt->execute(['property_id' => $propertyId, 'from_date' => $fromDate, 'to_date' => $toDate]);

        return $stmt->fetchAll();
    }
}
