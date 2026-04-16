<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\MySql\Repository;

use App\Application\Contracts\RequirementTotalsQueryRepositoryInterface;
use PDO;

final class MySqlRequirementTotalsQueryRepository implements RequirementTotalsQueryRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function totalsByEventIds(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $stmt = $this->connection->prepare("SELECT cer.cleaning_event_id, cer.item_id, SUM(cer.required_quantity) AS total_required FROM cleaning_event_requirements cer WHERE cer.cleaning_event_id IN ($placeholders) GROUP BY cer.cleaning_event_id, cer.item_id ORDER BY cer.cleaning_event_id");
        $stmt->execute($eventIds);

        return $stmt->fetchAll();
    }

    public function totalsByDay(?string $propertyId, string $date): array
    {
        if ($propertyId === null) {
            $stmt = $this->connection->prepare('SELECT ce.event_date, cer.item_id, SUM(cer.required_quantity) AS total_required
                FROM cleaning_events ce
                INNER JOIN cleaning_event_requirements cer ON cer.cleaning_event_id = ce.id
                WHERE ce.event_date = :event_date
                GROUP BY ce.event_date, cer.item_id');
            $stmt->execute(['event_date' => $date]);

            return $stmt->fetchAll();
        }

        $stmt = $this->connection->prepare('SELECT ce.event_date, cer.item_id, SUM(cer.required_quantity) AS total_required
            FROM cleaning_events ce
            INNER JOIN cleaning_event_requirements cer ON cer.cleaning_event_id = ce.id
            WHERE ce.property_id = :property_id AND ce.event_date = :event_date
            GROUP BY ce.event_date, cer.item_id');
        $stmt->execute(['property_id' => $propertyId, 'event_date' => $date]);

        return $stmt->fetchAll();
    }

    public function totalsByPropertyRange(string $propertyId, string $fromDate, string $toDate): array
    {
        $stmt = $this->connection->prepare('SELECT ce.event_date, cer.item_id, SUM(cer.required_quantity) AS total_required
            FROM cleaning_events ce
            INNER JOIN cleaning_event_requirements cer ON cer.cleaning_event_id = ce.id
            WHERE ce.property_id = :property_id AND ce.event_date BETWEEN :from_date AND :to_date
            GROUP BY ce.event_date, cer.item_id
            ORDER BY ce.event_date, cer.item_id');
        $stmt->execute([
            'property_id' => $propertyId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);

        return $stmt->fetchAll();
    }
}
